<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Extensions;

use PDO;
use PDOStatement;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\VerbosityLevel;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\QueryReflection\QueryReflector;

final class PdoStatementFetchDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return PDOStatement::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return 'fetchAll' === $methodReflection->getName();
    }

    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type
    {
        $args = $methodCall->getArgs();
		$defaultReturn = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();

		$statementType = $scope->getType($methodCall->var);

		if ($statementType instanceof GenericObjectType) {
			$genericTypes = $statementType->getTypes();

			if (count($genericTypes) !== 1) {
				return $defaultReturn;
			}

			$fetchType = PDO::FETCH_BOTH;
			if (count($args) > 0) {
				$fetchModeType = $scope->getType($args[0]->value);
				if (!$fetchModeType instanceof ConstantIntegerType) {
					return $defaultReturn;
				}
				$fetchType = $fetchModeType->getValue();
			}

			$resultType = $genericTypes[0];

			if ($fetchType === PDO::FETCH_NUM && $resultType instanceof ConstantArrayType) {
				$builder = ConstantArrayTypeBuilder::createEmpty();

				$keyTypes = $resultType->getKeyTypes();
				$valueTypes = $resultType->getValueTypes();

				foreach($keyTypes as $i => $keyType) {
					if ($keyType instanceof ConstantIntegerType) {
						$builder->setOffsetValueType($keyType, $valueTypes[$i]);
					}
				}

				return new ArrayType(new IntegerType(), $builder->getArray());
			}

			return new ArrayType(new IntegerType(), $resultType);
		}
		return $defaultReturn;
    }
}
