<?php declare(strict_types = 1);

namespace staabm\PHPStanDba;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Type;

final class PdoQueryDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtensio
{
	public function getClass(): string
	{
		return \PDO::class;
	}

	public function isMethodSupported(string $methodName): bool
	{
		return \in_array($methodName, ['query', 'prepare'], true);
	}

	public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type
	{
		if (count($methodCall->getArgs()) === 0) {
			return ParametersAcceptorSelector::selectFromArgs($scope, $methodCall->getArgs(), $methodReflection->getVariants())->getReturnType();
		}

		$methodName = $methodCall->name;

		if ($methodName === 'query') {
			// XXX build dynamically
			$arrayBuilder = ConstantArrayTypeBuilder::createEmpty();
			$arrayBuilder->setOffsetValueType(new ConstantStringType('id'), IntegerRangeType::fromInterval(1, null));
			$arrayBuilder->setOffsetValueType(new ConstantStringType('name'), new ConstantStringType('string'));
			return new GenericObjectType(\PDOStatement::class, [$arrayBuilder->getArray()]);
		}

		if ($methodName === 'prepare') {
			// TODO
			// return new Type('PDOStatement');
		}

		return ParametersAcceptorSelector::selectFromArgs($scope, $methodCall->getArgs(), $methodReflection->getVariants())->getReturnType();
	}
}
