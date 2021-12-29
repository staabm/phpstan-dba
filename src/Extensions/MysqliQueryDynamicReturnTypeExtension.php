<?php declare(strict_types=1);

namespace staabm\PHPStanDba\Extensions;

use mysqli;
use mysqli_result;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ConstantScalarType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\FloatType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\VerbosityLevel;
use staabm\PHPStanDba\QueryReflection\QueryReflection;

final class MysqliQueryDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension, DynamicFunctionReturnTypeExtension
{
	public function getClass(): string
	{
		return mysqli::class;
	}

	public function isMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getName() === 'query';
	}

	public function isFunctionSupported(FunctionReflection $functionReflection) : bool {
		return $functionReflection->getName() === 'mysqli_query';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope) : Type
	{
		$args = $functionCall->getArgs();

		if (count($args) < 2) {
			return ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
		}

		$queryReflection = new QueryReflection();
		$resultType = $queryReflection->getResultType($args[1]->value, $scope, QueryReflection::FETCH_TYPE_ASSOC);
		if ($resultType) {
			return TypeCombinator::union(
				new GenericObjectType(mysqli_result::class, [$resultType]),
				new ConstantBooleanType(false),
			);
		}

		return ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
	}

	public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type
	{
		$args = $methodCall->getArgs();

		if (count($args) < 1) {
			return ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();
		}

		$queryReflection = new QueryReflection();
		$resultType = $queryReflection->getResultType($args[0]->value, $scope, QueryReflection::FETCH_TYPE_ASSOC);
		if ($resultType) {
			return TypeCombinator::union(
				new GenericObjectType(mysqli_result::class, [$resultType]),
				new ConstantBooleanType(false),
			);
		}

		return ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();
	}

}
