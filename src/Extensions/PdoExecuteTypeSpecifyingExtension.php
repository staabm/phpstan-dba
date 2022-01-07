<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Extensions;

use PDOStatement;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ConstantScalarType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\MethodTypeSpecifyingExtension;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\QueryReflection\QueryReflector;

final class PdoExecuteTypeSpecifyingExtension implements MethodTypeSpecifyingExtension, TypeSpecifierAwareExtension
{
	private TypeSpecifier $typeSpecifier;

	private $nodeFinder;

	/*
	public function __construct(BetterNodeFinder $nodeFinder) {
		$this->nodeFinder = $nodeFinder;
	}
	*/

	public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
	{
		$this->typeSpecifier = $typeSpecifier;
	}

    public function getClass(): string
    {
        return PDOStatement::class;
    }

	public function isMethodSupported(MethodReflection $methodReflection, MethodCall $node, TypeSpecifierContext $context): bool
	{
		return 'execute' === $methodReflection->getName();
	}

	public function specifyTypes(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope, TypeSpecifierContext $context): \PHPStan\Analyser\SpecifiedTypes
	{
		$stmtType = $scope->getType($methodCall->var);
		$args = $methodCall->getArgs();

		if (count($args) === 0) {
			return $this->typeSpecifier->create($methodCall->var, $stmtType, TypeSpecifierContext::createTruthy());
		}

		$parameterTypes = $scope->getType($args[0]->value);
		$placeholders = $this->resolveParameters($parameterTypes);

		$queryString = 'SELECT email, adaid FROM ada WHERE adaid = :adaid';
		foreach($placeholders as $placeholderName => $value) {
			$queryString = str_replace($placeholderName, $value, $queryString);
		}

		$queryReflection = new QueryReflection();
		// $queryString = $queryReflection->resolveQueryString($args[0]->value, $scope);

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

}
