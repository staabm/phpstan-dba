<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\QueryReflection;

use Composer\InstalledVersions;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Scalar\Encapsed;
use PhpParser\Node\Scalar\EncapsedStringPart;
use PHPStan\Analyser\Scope;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use staabm\PHPStanDba\Analyzer\QueryPlanAnalyzerMysql;
use staabm\PHPStanDba\Analyzer\QueryPlanQueryResolver;
use staabm\PHPStanDba\Analyzer\QueryPlanResult;
use staabm\PHPStanDba\Ast\ExpressionFinder;
use staabm\PHPStanDba\DbaException;
use staabm\PHPStanDba\Error;
use staabm\PHPStanDba\PhpDoc\PhpDocUtil;
use staabm\PHPStanDba\SchemaReflection\SchemaReflection;
use staabm\PHPStanDba\SqlAst\ParserInference;
use staabm\PHPStanDba\UnresolvableQueryException;

final class QueryReflection
{
    private const UNNAMED_PATTERN = '\?';

    // see https://github.com/php/php-src/blob/01b3fc03c30c6cb85038250bb5640be3a09c6a32/ext/pdo/pdo_sql_parser.re#L48
    private const NAMED_PATTERN = ':[a-zA-Z0-9_]+';

    private const REGEX_UNNAMED_PLACEHOLDER = '{(["\'])([^"\']*\1)|(' . self::UNNAMED_PATTERN . ')}';

    private const REGEX_NAMED_PLACEHOLDER = '{(["\'])([^"\']*\1)|(' . self::NAMED_PATTERN . ')}';

    /**
     * @var QueryReflector|null
     */
    private static $reflector;

    /**
     * @var RuntimeConfiguration|null
     */
    private static $runtimeConfiguration;

    /**
     * @var SchemaReflection
     */
    private $schemaReflection;

    public function __construct(?DbaApi $dbaApi = null)
    {
        self::reflector()->setupDbaApi($dbaApi);
    }

    /**
     * @api
     */
    public static function setupReflector(QueryReflector $reflector, RuntimeConfiguration $runtimeConfiguration): void
    {
        self::$reflector = $reflector;
        self::$runtimeConfiguration = $runtimeConfiguration;
    }

    public function validateQueryString(string $queryString): ?Error
    {
        $queryType = self::getQueryType($queryString);

        if (self::getRuntimeConfiguration()->isAnalyzingWriteQueries()) {
            if (\in_array($queryType, [
                'INSERT',
                'DELETE',
                'UPDATE',
                'REPLACE',
            ], true)) {
                // turn write queries into explain, so we don't need to execute a query which might modify data
                $queryString = 'EXPLAIN ' . $queryString;
            } elseif ('SELECT' !== $queryType) {
                return null;
            }
        } else {
            if ('SELECT' !== $queryType) {
                return null;
            }
        }

        // this method cannot validate queries which contain placeholders.
        if (0 !== $this->countPlaceholders($queryString)) {
            return null;
        }

        return self::reflector()->validateQueryString($queryString);
    }

    /**
     * @param QueryReflector::FETCH_TYPE* $fetchType
     */
    public function getResultType(string $queryString, int $fetchType): ?Type
    {
        if ('SELECT' !== self::getQueryType($queryString)) {
            return null;
        }

        $reflector = self::reflector();
        $resultType = $reflector->getResultType($queryString, $fetchType);

        if (null !== $resultType) {
            if (! $resultType instanceof ConstantArrayType) {
                throw new ShouldNotHappenException();
            }

            if (
                self::getRuntimeConfiguration()->isUtilizingSqlAst()
            ) {
                if (! InstalledVersions::isInstalled('sqlftw/sqlftw')) {
                    throw new \Exception('sqlftw/sqlftw is required to utilize the sql ast. Please install it via composer.');
                }
                if ($reflector instanceof PdoPgSqlQueryReflector) {
                    throw new \Exception('SQL AST inference is only supported for mysql backends for now.');
                }
                $parserInference = new ParserInference($this->getSchemaReflection());
                $resultType = $parserInference->narrowResultType($queryString, $resultType);
            }

            if (self::getRuntimeConfiguration()->isStringifyTypes()) {
                return $this->stringifyResult($resultType);
            }
        }

        return $resultType;
    }

    private function stringifyResult(Type $type): Type
    {
        if (! $type instanceof ConstantArrayType) {
            return $type;
        }

        $builder = ConstantArrayTypeBuilder::createEmpty();

        $keyTypes = $type->getKeyTypes();
        foreach ($type->getValueTypes() as $i => $valueType) {
            $builder->setOffsetValueType($keyTypes[$i], $this->stringifyType($valueType));
        }

        return $builder->getArray();
    }

    private function stringifyType(Type $type): Type
    {
        $containsNull = TypeCombinator::containsNull($type);

        $numberType = new UnionType([new IntegerType(), new FloatType()]);

        if ($numberType->isSuperTypeOf(TypeCombinator::removeNull($type))->yes()) {
            $stringified = new IntersectionType([
                new StringType(),
                new AccessoryNumericStringType(),
            ]);

            if ($containsNull) {
                return TypeCombinator::addNull($stringified);
            }

            return $stringified;
        }

        return $type;
    }

    public function getSchemaReflection(): SchemaReflection
    {
        if (null === $this->schemaReflection) {
            $this->schemaReflection = new SchemaReflection(function ($queryString) {
                return self::reflector()->getResultType($queryString, QueryReflector::FETCH_TYPE_ASSOC);
            });
        }

        return $this->schemaReflection;
    }

    /**
     * Determine if a query will be resolvable.
     *
     * - If yes, the query is a literal string.
     * - If no, the query is a non-literal string or mixed type.
     * - If maybe, the query is neither of the two.
     *
     * We will typically skip processing of queries that return no, which are
     * likely part of a software abstraction layer that we know nothing about.
     */
    public function isResolvable(Expr $queryExpr, Scope $scope): TrinaryLogic
    {
        $type = $scope->getType($queryExpr);
        if ($type->isLiteralString()->yes()) {
            return TrinaryLogic::createYes();
        }
        $isStringOrMixed = $type->isSuperTypeOf(new StringType());

        return $isStringOrMixed->negate();
    }

    /**
     * @return iterable<string>
     *
     * @throws UnresolvableQueryException
     */
    public function resolvePreparedQueryStrings(Expr $queryExpr, Type $parameterTypes, Scope $scope): iterable
    {
        $type = $scope->getType($queryExpr);

        if ($type instanceof UnionType) {
            $parameters = $this->resolveParameters($parameterTypes);
            if (null === $parameters) {
                return null;
            }

            foreach ($type->getConstantStrings() as $constantString) {
                $queryString = $constantString->getValue();
                $queryString = $this->replaceParameters($queryString, $parameters);
                yield $this->normalizeQueryString($queryString);
            }

            return;
        }

        $queryString = $this->resolvePreparedQueryString($queryExpr, $parameterTypes, $scope);
        if (null !== $queryString) {
            yield $this->normalizeQueryString($queryString);
        }
    }

    /**
     * @api
     *
     * @deprecated use resolvePreparedQueryStrings() instead
     *
     * @throws UnresolvableQueryException
     */
    public function resolvePreparedQueryString(Expr $queryExpr, Type $parameterTypes, Scope $scope): ?string
    {
        $queryString = $this->resolveQueryExpr($queryExpr, $scope);

        if (null === $queryString) {
            return null;
        }

        $parameters = $this->resolveParameters($parameterTypes);
        if (null === $parameters) {
            return null;
        }

        return $this->replaceParameters($queryString, $parameters);
    }

    /**
     * @return iterable<string>
     *
     * @throws UnresolvableQueryException
     */
    public function resolveQueryStrings(Expr $queryExpr, Scope $scope): iterable
    {
        $type = $scope->getType($queryExpr);

        $constantStrings = $type->getConstantStrings();
        if (count($constantStrings) > 0) {
            foreach ($constantStrings as $constantString) {
                yield QuerySimulation::stripComments($this->normalizeQueryString($constantString->getValue()));
            }

            return;
        }

        $queryString = $this->resolveQueryExpr($queryExpr, $scope);
        if (null !== $queryString) {
            $normalizedQuery = QuerySimulation::stripComments($this->normalizeQueryString($queryString));

            // query simulation might lead in a invalid query, skip those
            $error = $this->validateQueryString($normalizedQuery);
            if ($error === null) {
                yield $normalizedQuery;
            }
        }
    }

    /**
     * Make sure query string work consistently across operating systems.
     */
    private function normalizeQueryString(string $queryString): string
    {
        return str_replace("\r\n", "\n", trim($queryString));
    }

    /**
     * @deprecated use resolveQueryStrings() instead
     *
     * @throws UnresolvableQueryException
     */
    public function resolveQueryString(Expr $queryExpr, Scope $scope): ?string
    {
        return $this->resolveQueryExpr($queryExpr, $scope);
    }

    /**
     * @throws UnresolvableQueryException
     */
    private function resolveQueryExpr(Expr $queryExpr, Scope $scope): ?string
    {
        if ($queryExpr instanceof Expr\Variable) {
            $finder = new ExpressionFinder();
            $queryStringExpr = $finder->findAssignmentExpression($queryExpr);

            if (null !== $queryStringExpr) {
                return $this->resolveQueryStringExpr($queryStringExpr, $scope);
            }
        }

        return $this->resolveQueryStringExpr($queryExpr, $scope);
    }

    /**
     * @throws UnresolvableQueryException
     */
    private function resolveQueryStringExpr(Expr $queryExpr, Scope $scope, bool $resolveVariables = true): ?string
    {
        if (true === $resolveVariables && $queryExpr instanceof Expr\Variable) {
            $finder = new ExpressionFinder();
            // atm we cannot reason about variables which are manipulated via assign ops
            $assignExpr = $finder->findAssignmentExpression($queryExpr, true);

            if (null !== $assignExpr) {
                return $this->resolveQueryStringExpr($assignExpr, $scope);
            }

            return $this->resolveQueryStringExpr($queryExpr, $scope, false);
        }

        if ($queryExpr instanceof Expr\CallLike) {
            $placeholder = PhpDocUtil::matchInferencePlaceholder($queryExpr, $scope);
            if (null !== $placeholder) {
                return $placeholder;
            }

            if ('sql' === PhpDocUtil::matchTaintEscape($queryExpr, $scope)) {
                return '1';
            }
        }

        if ($queryExpr instanceof Concat) {
            $left = $queryExpr->left;
            $right = $queryExpr->right;

            $leftString = $this->resolveQueryStringExpr($left, $scope);
            $rightString = $this->resolveQueryStringExpr($right, $scope);

            if (null === $leftString || null === $rightString) {
                return null;
            }

            return $leftString . $rightString;
        }

        if ($queryExpr instanceof Encapsed) {
            $string = '';
            foreach ($queryExpr->parts as $part) {
                $resolvedPart = $this->resolveQueryStringExpr($part, $scope);
                if (null === $resolvedPart) {
                    return null;
                }
                $string .= $resolvedPart;
            }

            return $string;
        }

        if ($queryExpr instanceof EncapsedStringPart) {
            return $queryExpr->value;
        }

        $type = $scope->getType($queryExpr);

        return QuerySimulation::simulateParamValueType($type, false);
    }

    public static function getQueryType(string $query): ?string
    {
        $query = ltrim($query);

        if (1 === preg_match('/^\s*\(?\s*(SELECT|SHOW|UPDATE|INSERT|DELETE|REPLACE|CREATE|CALL|OPTIMIZE)/i', $query, $matches)) {
            return strtoupper($matches[1]);
        }

        return null;
    }

    /**
     * Resolves prepared statement parameter types.
     *
     * @return array<string|int, Parameter>|null
     *
     * @throws UnresolvableQueryException
     */
    public function resolveParameters(Type $parameterTypes): ?array
    {
        $parameters = [];

        if ($parameterTypes instanceof UnionType) {
            foreach ($parameterTypes->getConstantArrays() as $constantArray) {
                $parameters = $parameters + $this->resolveConstantArray($constantArray, true);
            }

            return $parameters;
        }

        if ($parameterTypes instanceof ConstantArrayType) {
            return $this->resolveConstantArray($parameterTypes, false);
        }

        return null;
    }

    /**
     * @return array<string|int, Parameter>
     *
     * @throws UnresolvableQueryException
     */
    private function resolveConstantArray(ConstantArrayType $parameterTypes, bool $forceOptional): array
    {
        $parameters = [];

        $keyTypes = $parameterTypes->getKeyTypes();
        $valueTypes = $parameterTypes->getValueTypes();
        $optionalKeys = $parameterTypes->getOptionalKeys();

        foreach ($keyTypes as $i => $keyType) {
            $isOptional = \in_array($i, $optionalKeys, true);
            if ($forceOptional) {
                $isOptional = true;
            }

            if ($keyType instanceof ConstantStringType) {
                $placeholderName = $keyType->getValue();

                if ('' === $placeholderName) {
                    throw new ShouldNotHappenException('Empty placeholder name');
                }

                $param = new Parameter(
                    $placeholderName,
                    $valueTypes[$i],
                    QuerySimulation::simulateParamValueType($valueTypes[$i], true),
                    $isOptional
                );

                $parameters[$param->name] = $param;
            } elseif ($keyType instanceof ConstantIntegerType) {
                $param = new Parameter(
                    null,
                    $valueTypes[$i],
                    QuerySimulation::simulateParamValueType($valueTypes[$i], true),
                    $isOptional
                );

                $parameters[$keyType->getValue()] = $param;
            }
        }

        return $parameters;
    }

    /**
     * @param array<string|int, Parameter> $parameters
     */
    private function replaceParameters(string $queryString, array $parameters): string
    {
        $replaceFirst = function (string $haystack, string $needle, string $replace) {
            $pos = strpos($haystack, $needle);
            if (false !== $pos) {
                return substr_replace($haystack, $replace, $pos, \strlen($needle));
            }

            return $haystack;
        };

        foreach ($parameters as $placeholderKey => $parameter) {
            $value = $parameter->simulatedValue;

            if (\is_string($value)) {
                // XXX escaping
                $value = "'" . $value . "'";
            } elseif (null === $value) {
                $value = 'NULL';
            } else {
                $value = (string) $value;
            }

            if (\is_string($placeholderKey)) {
                $queryString = (string) preg_replace('/' . $placeholderKey . '\\b/', $value, $queryString);
            } else {
                $queryString = $replaceFirst($queryString, '?', $value);
            }
        }

        return $queryString;
    }

    private static function reflector(): QueryReflector
    {
        if (null === self::$reflector) {
            throw new DbaException('Reflector not initialized. Make sure a phpstan bootstrap file is configured which calls ' . __CLASS__ . '::setupReflector().');
        }

        return self::$reflector;
    }

    public static function getRuntimeConfiguration(): RuntimeConfiguration
    {
        if (null === self::$runtimeConfiguration) {
            throw new DbaException('Runtime configuration not initialized. Make sure a phpstan bootstrap file is configured which calls ' . __CLASS__ . '::setupReflector().');
        }

        return self::$runtimeConfiguration;
    }

    /**
     * @return 0|positive-int
     */
    public function countPlaceholders(string $queryString): int
    {
        // match named placeholders first, as the regex involved is more specific/less error prone
        $namedPlaceholders = $this->extractNamedPlaceholders($queryString);

        // pdo does not support mixing of named and '?' placeholders
        if ([] !== $namedPlaceholders) {
            return \count($namedPlaceholders);
        }

        if (preg_match_all(self::REGEX_UNNAMED_PLACEHOLDER, $queryString, $matches) > 0) {
            $candidates = $matches[0];

            // filter placeholders within quoted strings
            $candidates = array_filter($candidates, function ($candidate) {
                return '"' !== $candidate[0] && "'" !== $candidate[0];
            });

            return \count($candidates);
        }

        return 0;
    }

    /**
     * @param array<string|int, Parameter> $parameters
     */
    public function containsNamedPlaceholders(string $queryString, array $parameters): bool
    {
        $namedPlaceholders = $this->extractNamedPlaceholders($queryString);

        if ([] !== $namedPlaceholders) {
            return true;
        }

        foreach ($parameters as $parameter) {
            if (null !== $parameter->name) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    public function extractNamedPlaceholders(string $queryString): array
    {
        if (preg_match_all(self::REGEX_NAMED_PLACEHOLDER, $queryString, $matches) > 0) {
            $candidates = $matches[0];

            // filter placeholders within quoted strings
            $candidates = array_filter($candidates, function ($candidate) {
                return '"' !== $candidate[0] && "'" !== $candidate[0];
            });

            // filter placeholders which occur several times
            return array_unique($candidates);
        }

        return [];
    }

    /**
     * @return iterable<array-key, QueryPlanResult>
     */
    public function analyzeQueryPlan(Scope $scope, Expr $queryExpr, ?Type $parameterTypes): iterable
    {
        $reflector = self::reflector();

        if (! $reflector instanceof RecordingReflector) {
            throw new DbaException('Query plan analysis is only supported with a recording reflector');
        }
        if ($reflector instanceof PdoPgSqlQueryReflector) {
            throw new DbaException('Query plan analysis is not yet supported with the pdo-pgsql reflector, see https://github.com/staabm/phpstan-dba/issues/378');
        }

        $ds = $reflector->getDatasource();
        if (null === $ds) {
            throw new DbaException(sprintf('Unable to create datasource from %s', \get_class($reflector)));
        }
        $queryPlanAnalyzer = new QueryPlanAnalyzerMysql($ds);

        $queryResolver = new QueryPlanQueryResolver();
        foreach ($queryResolver->resolve($scope, $queryExpr, $parameterTypes) as $queryString) {
            if ('' === $queryString) {
                continue;
            }

            if ('SELECT' !== self::getQueryType($queryString)) {
                continue;
            }

            if ($reflector->validateQueryString($queryString) instanceof Error) {
                continue;
            }

            yield $queryPlanAnalyzer->analyze($queryString);
        }
    }
}
