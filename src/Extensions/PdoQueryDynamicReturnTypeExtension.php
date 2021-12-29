<?php declare(strict_types=1);

namespace staabm\PHPStanDba\Extensions;

use PDO;
use PDOStatement;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ConstantScalarType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\FloatType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
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

		if (count($args) < 2) {
			return $defaultReturn;
		}

		$fetchModeType = $scope->getType($args[1]->value);
		if (!$fetchModeType instanceof ConstantIntegerType || $fetchModeType->getValue() !== PDO::FETCH_ASSOC) {
			return $defaultReturn;
		}

		$queryReflection = new QueryReflection();
		$resultType = $queryReflection->getResultType($args[0]->value, $scope);
		if ($resultType) {
			return $resultType;
		}

		return $defaultReturn;
	}

}
