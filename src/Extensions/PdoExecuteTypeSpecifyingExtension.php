<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Extensions;

use PDOStatement;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\FunctionLike;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Reflection\MethodReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ConstantScalarType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\MethodTypeSpecifyingExtension;
use PHPStan\Type\Type;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\QueryReflection\QueryReflector;
use Symplify\Astral\ValueObject\AttributeKey;

final class PdoExecuteTypeSpecifyingExtension implements MethodTypeSpecifyingExtension, TypeSpecifierAwareExtension
{
    private TypeSpecifier $typeSpecifier;
    private NodeFinder $nodeFinder;

    public function __construct()
    {
        $this->nodeFinder = new NodeFinder();
    }

    public function getClass(): string
    {
        return PDOStatement::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection, MethodCall $node, TypeSpecifierContext $context): bool
    {
        return 'execute' === $methodReflection->getName();
    }

    public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
    {
        $this->typeSpecifier = $typeSpecifier;
    }

    public function specifyTypes(MethodReflection $methodReflection, MethodCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
    {
        // keep original param name because named-parameters
        $methodCall = $node;
        $stmtType = $scope->getType($methodCall->var);

        $inferedType = $this->inferStatementType($methodCall, $scope);
        if (null !== $inferedType) {
            return $this->typeSpecifier->create($methodCall->var, $inferedType, TypeSpecifierContext::createTruthy(), true);
        }

        return $this->typeSpecifier->create($methodCall->var, $stmtType, TypeSpecifierContext::createTruthy());
    }

    private function inferStatementType(MethodCall $methodCall, Scope $scope): ?Type
    {
        $args = $methodCall->getArgs();

        if (0 === \count($args)) {
            return null;
        }

        $parameterTypes = $scope->getType($args[0]->value);
        $parameters = $this->resolveParameters($parameterTypes);
        $queryExpr = $this->findQueryStringExpression($methodCall);
        if (null === $queryExpr) {
            return null;
        }

        // resolve query parameter from "prepare"
        if ($queryExpr instanceof MethodCall) {
            $args = $queryExpr->getArgs();
            $queryExpr = $args[0]->value;
        }

        $queryReflection = new QueryReflection();
        $queryString = $queryReflection->resolveQueryString($queryExpr, $scope);
        if (null === $queryString) {
            return null;
        }

        $reflectionFetchType = QueryReflector::FETCH_TYPE_BOTH;
        $queryString = $this->replaceParameters($queryString, $parameters);
        $resultType = $queryReflection->getResultType($queryString, $reflectionFetchType);

        if ($resultType) {
            return new GenericObjectType(PDOStatement::class, [$resultType]);
        }

        return null;
    }

    /**
     * @param array<string|int, string> $parameters
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

        foreach ($parameters as $placeholderKey => $value) {
            if (\is_int($placeholderKey)) {
                $queryString = $replaceFirst($queryString, '?', $value);
            } else {
                $queryString = str_replace($placeholderKey, $value, $queryString);
            }
        }

        return $queryString;
    }

    /**
     * @return array<string|int, string>
     */
    public function resolveParameters(Type $parameterTypes): array
    {
        $parameters = [];

        if ($parameterTypes instanceof ConstantArrayType) {
            $keyTypes = $parameterTypes->getKeyTypes();
            $valueTypes = $parameterTypes->getValueTypes();

            foreach ($keyTypes as $i => $keyType) {
                if ($keyType instanceof ConstantStringType) {
                    $placeholderName = $keyType->getValue();

                    if (!str_starts_with($placeholderName, ':')) {
                        $placeholderName = ':'.$placeholderName;
                    }

                    if ($valueTypes[$i] instanceof ConstantScalarType) {
                        $parameters[$placeholderName] = (string) ($valueTypes[$i]->getValue());
                    }
                } elseif ($keyType instanceof ConstantIntegerType) {
                    if ($valueTypes[$i] instanceof ConstantScalarType) {
                        $parameters[$keyType->getValue()] = (string) ($valueTypes[$i]->getValue());
                    }
                }
            }
        }

        return $parameters;
    }

    private function findQueryStringExpression(MethodCall $methodCall): ?Expr
    {
        // todo: use astral simpleNameResolver
        $nameResolver = function ($node) {
            if (\is_string($node->name)) {
                return $node->name;
            }
            if ($node->name instanceof Node\Identifier) {
                return $node->name->toString();
            }
        };

        $current = $methodCall;
        while (null !== $current) {
            /** @var Assign|null $assign */
            $assign = $this->findFirstPreviousOfNode($current, function ($node) {
                return $node instanceof Assign;
            });

            if (null !== $assign && $nameResolver($assign->var) === $nameResolver($methodCall->var)) {
                return $assign->expr;
            }

            $current = $assign;
        }

        return null;
    }

    /**
     * @param callable(Node $node):bool $filter
     */
    public function findFirstPreviousOfNode(Node $node, callable $filter): ?Node
    {
        // move to previous expression
        $previousStatement = $node->getAttribute(AttributeKey::PREVIOUS);
        if (null !== $previousStatement) {
            if (!$previousStatement instanceof Node) {
                throw new ShouldNotHappenException();
            }
            $foundNode = $this->findFirst([$previousStatement], $filter);
            // we found what we need
            if (null !== $foundNode) {
                return $foundNode;
            }

            return $this->findFirstPreviousOfNode($previousStatement, $filter);
        }

        $parent = $node->getAttribute(AttributeKey::PARENT);
        if ($parent instanceof FunctionLike) {
            return null;
        }

        if ($parent instanceof Node) {
            return $this->findFirstPreviousOfNode($parent, $filter);
        }

        return null;
    }

    /**
     * @param Node|Node[] $nodes
     * @param callable(Node $node):bool $filter
     */
    public function findFirst(Node|array $nodes, callable $filter): ?Node
    {
        return $this->nodeFinder->findFirst($nodes, $filter);
    }
}
