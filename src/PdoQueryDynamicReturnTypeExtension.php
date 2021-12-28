<?php declare(strict_types=1);

namespace staabm\PHPStanDba;

use PDOStatement;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ConstantScalarType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\StringType;
use PHPStan\Type\TypeCombinator;

final class PdoQueryDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
	public function getClass(): string
	{
		return \PDO::class;
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

			$resultType = $this->inferResultTypeFromQuery($queryString);
			if ($resultType) {
				//var_dump($resultType->describe(VerbosityLevel::precise()));
				return $resultType;
			}
		}

		return $defaultReturn;
	}

	private function inferResultTypeFromQuery(string $queryString): ?Type
	{
		if ($queryString === 'SELECT name, id FROM foo') {
			// XXX build dynamically
			$arrayBuilder = ConstantArrayTypeBuilder::createEmpty();
			$arrayBuilder->setOffsetValueType(new ConstantStringType('id'), IntegerRangeType::fromInterval(0, null));
			$arrayBuilder->setOffsetValueType(new ConstantStringType('name'), new StringType());

			//return TypeCombinator::union(
			return	new GenericObjectType(PDOStatement::class, [$arrayBuilder->getArray()]);
			//	new ConstantBooleanType(false)
			//);
		}

		return null;
	}
}
