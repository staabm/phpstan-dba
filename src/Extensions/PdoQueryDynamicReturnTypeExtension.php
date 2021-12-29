<?php declare(strict_types=1);

namespace staabm\PHPStanDba\Extensions;

use PDO;
use PDOStatement;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ConstantScalarType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\MixedType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\VerbosityLevel;
use staabm\PHPStanDba\QueryReflection\QueryReflection;

final class PdoQueryDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
	public function getClass(): string
	{
		return PDO::class;
	}

	public function isMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getName() === 'query';
	}

	public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type
	{
		$args = $methodCall->getArgs();
		$mixed = new MixedType(true);

		$defaultReturn = TypeCombinator::union(
			new GenericObjectType(PDOStatement::class, [new ArrayType($mixed, $mixed)]),
			new ConstantBooleanType(false)
		);

		if (count($args) === 0) {
			return $defaultReturn;
		}

		$queryType = $scope->getType($args[0]->value);
		if ($queryType instanceof ConstantScalarType) {
			$queryString = $queryType->getValue();

			$queryReflection = new QueryReflection();
			$resultType = $queryReflection->getResultType($queryString);
			if ($resultType) {
				return $resultType;
			}
		}

		return $defaultReturn;
	}
}
