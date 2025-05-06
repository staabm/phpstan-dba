<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name\FullyQualified;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\ObjectType;
use staabm\PHPStanDba\DibiReflection\DibiReflection;
use staabm\PHPStanDba\QueryReflection\DbaApi;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\QueryReflection\QueryReflector;

/**
 * @implements Rule<CallLike>
 *
 * @see SyntaxErrorInDibiPreparedStatementMethodRuleTest
 */
final class SyntaxErrorInDibiPreparedStatementMethodRule implements Rule
{
    /**
     * @var list<string>
     */
    private array $classMethods;

    private ReflectionProvider $reflectionProvider;

    /**
     * @param list<string> $classMethods
     */
    public function __construct(array $classMethods, ReflectionProvider $reflectionProvider)
    {
        $this->classMethods = $classMethods;
        $this->reflectionProvider = $reflectionProvider;
    }

    public function getNodeType(): string
    {
        return CallLike::class;
    }

    public function processNode(Node $callLike, Scope $scope): array
    {
        if ($callLike instanceof MethodCall) {
            if (! $callLike->name instanceof Node\Identifier) {
                return [];
            }

            $methodReflection = $scope->getMethodReflection($scope->getType($callLike->var), $callLike->name->toString());
        } elseif ($callLike instanceof New_) {
            if (! $callLike->class instanceof FullyQualified) {
                return [];
            }
            $methodReflection = $scope->getMethodReflection(new ObjectType($callLike->class->toCodeString()), '__construct');
        } else {
            return [];
        }

        if (null === $methodReflection) {
            return [];
        }

        $unsupportedMethod = true;
        foreach ($this->classMethods as $classMethod) {
            sscanf($classMethod, '%[^::]::%s', $className, $methodName);
            if (! \is_string($className) || ! \is_string($methodName)) {
                throw new ShouldNotHappenException('Invalid classMethod definition');
            }

            if ($methodName === $methodReflection->getName() &&
                (
                    $methodReflection->getDeclaringClass()->getName() === $className
                    || ($this->reflectionProvider->hasClass($className) && $methodReflection->getDeclaringClass()->isSubclassOfClass($this->reflectionProvider->getClass($className)))
                )
            ) {
                $unsupportedMethod = false;
                break;
            }
        }

        if ($unsupportedMethod) {
            return [];
        }

        return $this->checkErrors($callLike, $scope, $methodReflection);
    }

    /**
     * @param MethodCall|New_ $callLike
     *
     * @return list<IdentifierRuleError>
     */
    private function checkErrors(CallLike $callLike, Scope $scope, MethodReflection $methodReflection): array
    {
        $args = $callLike->getArgs();

        if (\count($args) < 1) {
            return [];
        }

        $queryReflection = new QueryReflection(new DbaApi(DbaApi::API_DIBI));
        $queryParameters = [];
        $errors = [];

        foreach ($args as $arg) {
            $parameterExpr = $arg->value;
            $parameterType = $scope->getType($parameterExpr);

            if ($parameterType->isString()->yes()) {
                $resolvedString = $queryReflection->resolveQueryString($parameterExpr, $scope);

                if (null === $resolvedString) {
                    $queryParameters[] = $parameterType;
                } else {
                    $queryParameters[] = $resolvedString;
                }
            } elseif ($parameterType->isConstantArray()->yes()) {
                $constantArray = [];
                $arrays = $parameterType->getConstantArrays();
                foreach ($arrays as $array) {
                    foreach ($array->getKeyTypes() as $i => $keyType) {
                        $constantArray[$keyType->getValue()] = $array->getValueTypes()[$i];
                    }
                }


                $queryParameters[] = $constantArray;
            } else {
                $queryParameters[] = $parameterType;
            }
        }

        if (! \is_string($queryParameters[0])) {
            return [];
        }

        $stringParameterCount = 0;

        foreach ($queryParameters as $queryParameter) {
            if (\is_string($queryParameter)) {
                $stringParameterCount = $stringParameterCount + 1;
            }
        }

        $placeholders = [];
        // see https://dibiphp.com/en/documentation#toc-modifiers
        preg_match_all('#%(sN|bin|by|lmt|b|iN|f|dt|sql|ex|in|i|l|m|and|or|s|t|d|~?like~?|n|ofs|N)#', $queryParameters[0], $placeholders, \PREG_SET_ORDER);
        $placeholderCount = \count($placeholders);
        $parameterCount = \count($queryParameters) - 1;

        // check that it's not the dibi magic insert statement $this->connection->query('INSERT into apps', ['xx' => ...])
        // in that case it does not make sense to validate placeholder count because we know it won't match
        if (1 === $stringParameterCount && 'INSERT' !== QueryReflection::getQueryType($queryParameters[0])) {
            if ($parameterCount !== $placeholderCount) {
                $placeholderExpectation = sprintf('Query expects %s placeholder', $placeholderCount);
                if ($placeholderCount > 1) {
                    $placeholderExpectation = sprintf('Query expects %s placeholders', $placeholderCount);
                }

                if (0 === $parameterCount) {
                    $parameterActual = 'but no values are given';
                } else {
                    $parameterActual = sprintf('but %s value is given', $parameterCount);
                    if ($parameterCount > 1) {
                        $parameterActual = sprintf('but %s values are given', $parameterCount);
                    }
                }

                return [
                    RuleErrorBuilder::message($placeholderExpectation . ', ' . $parameterActual . '.')->identifier('dba.placeholderMismatch')->line($callLike->getStartLine())->build(),
                ];
            }
        }

        if ($stringParameterCount > 1) {
            // means syntax like `query('update app set', [...], ' where x = %i', 1)`
            return [];
        }

        if ($placeholderCount !== $parameterCount) {
            // means syntax like `query('insert into app', [...])`
            return [];
        }

        $dibiReflection = new DibiReflection();
        $queryParameters[0] = $dibiReflection->rewriteQuery($queryParameters[0]);

        if (null === $queryParameters[0]) {
            return [];
        }

        $validity = $queryReflection->validateQueryString($queryParameters[0]);

        if (null !== $validity) {
            return [RuleErrorBuilder::message($validity->asRuleMessage())->identifier('dba.syntaxError')->line($callLike->getStartLine())->build()];
        }

        $result = $queryReflection->getResultType($queryParameters[0], QueryReflector::FETCH_TYPE_BOTH);

        if ($result instanceof ConstantArrayType) {
            // compensate fetch both
            $columnsInResult = \count($result->getValueTypes()) / 2;

            if ('fetchPairs' === $methodReflection->getName() && 2 !== $columnsInResult) {
                return [RuleErrorBuilder::message('fetchPairs requires exactly 2 selected columns, got ' . $columnsInResult . '.')->identifier('dba.error')->line($callLike->getStartLine())->build()];
            }

            if ('fetchSingle' === $methodReflection->getName() && 1 !== $columnsInResult) {
                return [RuleErrorBuilder::message('fetchSingle requires exactly 1 selected column, got ' . $columnsInResult . '.')->identifier('dba.error')->line($callLike->getStartLine())->build()];
            }
        }

        return $errors;
    }
}
