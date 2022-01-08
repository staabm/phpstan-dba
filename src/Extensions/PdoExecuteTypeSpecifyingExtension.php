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
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ConstantScalarType;
use PHPStan\Type\Type;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\MethodTypeSpecifyingExtension;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\QueryReflection\QueryReflector;
use Rector\NodeTypeResolver\Node\AttributeKey;

final class PdoExecuteTypeSpecifyingExtension implements MethodTypeSpecifyingExtension, TypeSpecifierAwareExtension
{
	private TypeSpecifier $typeSpecifier;
	private NodeFinder $nodeFinder;

	public function __construct() {
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

	public function specifyTypes(MethodReflection $methodReflection, MethodCall $node, Scope $scope, TypeSpecifierContext $context): \PHPStan\Analyser\SpecifiedTypes
	{
		// keep original param name because named-parameters
		$methodCall = $node;

		$stmtType = $scope->getType($methodCall->var);
		$args = $methodCall->getArgs();

		if (count($args) === 0) {
			return $this->typeSpecifier->create($methodCall->var, $stmtType, TypeSpecifierContext::createTruthy());
		}

		$parameterTypes = $scope->getType($args[0]->value);
		$placeholders = $this->resolveParameters($parameterTypes);
		$queryExpr = $this->findQueryStringExpression($methodCall);
		if ($queryExpr === null) {
			return $this->typeSpecifier->create($methodCall->var, $stmtType, TypeSpecifierContext::createTruthy());
		}
		// resolve query parameter from "prepare"
		if ($queryExpr instanceof MethodCall) {
			$args = $queryExpr->getArgs();
			$queryExpr = $args[0]->value;
		}

		$queryReflection = new QueryReflection();
		$queryString = $queryReflection->resolveQueryString($queryExpr, $scope);
		if ($queryString === null) {
			return $this->typeSpecifier->create($methodCall->var, $stmtType, TypeSpecifierContext::createTruthy());
		}

		foreach($placeholders as $placeholderName => $value) {
			$queryString = str_replace($placeholderName, $value, $queryString);
		}
		$reflectionFetchType = QueryReflector::FETCH_TYPE_BOTH;
		$resultType = $queryReflection->getResultType($queryString, $reflectionFetchType);

		if ($resultType) {
			$stmtType = new GenericObjectType(PDOStatement::class, [$resultType]);
		}

		return $this->typeSpecifier->create($methodCall->var, $stmtType, TypeSpecifierContext::createTruthy(), true);
	}

	/**
	 * @return array<string, string>
	 */
	public function resolveParameters(Type $parameterTypes): array
	{
		$placeholders = array();

		if ($parameterTypes instanceof ConstantArrayType) {
			$keyTypes = $parameterTypes->getKeyTypes();
			$valueTypes = $parameterTypes->getValueTypes();

			foreach ($keyTypes as $i => $keyType) {
				if ($keyType instanceof ConstantStringType) {
					$placeholderName = $keyType->getValue();

					if (!str_starts_with($placeholderName, ':')) {
						$placeholderName = ':' . $placeholderName;
					}

					if ($valueTypes[$i] instanceof ConstantScalarType) {
						$placeholders[$placeholderName] = (string)($valueTypes[$i]->getValue());
					}
				}
			}
		}
		return $placeholders;
	}

	private function findQueryStringExpression(MethodCall $methodCall): ?Expr
	{
		// todo: use astral simpleNameResolver
		$nameResolver = function($node) {
			if (is_string($node->name)) {
				return $node->name;
			}
			if ($node->name instanceof Node\Identifier) {
				return $node->name->toString();
			}
		};

		$current = $methodCall;
		do {
			/** @var Assign|null $assign */
			$assign = $this->findFirstPreviousOfNode($current, function($node) {
				return $node instanceof Assign;
			});

			if ($assign !== null && $nameResolver($assign->var) === $nameResolver($methodCall->var)) {
				return $assign->expr;
			}

			$current = $assign;
		} while ($assign !== null);

		return null;
	}

	/**
	 * @param callable(Node $node):?Node $filter
	 */
	public function findFirstPreviousOfNode(Node $node, callable $filter): ?Node
	{
		// move to previous expression
		$previousStatement = $node->getAttribute(AttributeKey::PREVIOUS_NODE);
		if ($previousStatement !== null) {
			$foundNode = $this->findFirst([$previousStatement], $filter);
			// we found what we need
			if ($foundNode !== null) {
				return $foundNode;
			}

			return $this->findFirstPreviousOfNode($previousStatement, $filter);
		}

		$parent = $node->getAttribute(AttributeKey::PARENT_NODE);
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
	 */
	public function findFirst(Node | array $nodes, callable $filter): ?Node
	{
		return $this->nodeFinder->findFirst($nodes, $filter);
	}
}
