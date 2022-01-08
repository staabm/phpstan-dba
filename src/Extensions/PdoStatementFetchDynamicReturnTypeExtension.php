<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Extensions;

use PDO;
use PDOStatement;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;

final class PdoStatementFetchDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    /**
     * @var PhpVersion
     */
    private $phpVersion;

    public function __construct(PhpVersion $phpVersion)
    {
        $this->phpVersion = $phpVersion;
    }

    public function getClass(): string
    {
        return PDOStatement::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return \in_array($methodReflection->getName(), ['fetchAll', 'fetch'], true);
    }

    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type
    {
        $args = $methodCall->getArgs();
        $defaultReturn = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();

        // since php8 the default error mode changed to exception, therefore false returns are not longer possible
        if ($this->phpVersion->getVersionId() >= 80000 && !$defaultReturn instanceof MixedType) {
            $defaultReturn = TypeCombinator::remove($defaultReturn, new ConstantBooleanType(false));
        }

        $statementType = $scope->getType($methodCall->var);

        if ($statementType instanceof GenericObjectType) {
            $genericTypes = $statementType->getTypes();

            if (1 !== \count($genericTypes)) {
                return $defaultReturn;
            }

            $fetchType = PDO::FETCH_BOTH;
            if (\count($args) > 0) {
                $fetchModeType = $scope->getType($args[0]->value);
                if (!$fetchModeType instanceof ConstantIntegerType) {
                    return $defaultReturn;
                }
                $fetchType = $fetchModeType->getValue();

                if (!\in_array($fetchType, [PDO::FETCH_ASSOC, PDO::FETCH_NUM, PDO::FETCH_BOTH])) {
                    return $defaultReturn;
                }
            }

            $resultType = $genericTypes[0];

            if ((PDO::FETCH_NUM === $fetchType || PDO::FETCH_ASSOC === $fetchType) && $resultType instanceof ConstantArrayType) {
                $builder = ConstantArrayTypeBuilder::createEmpty();

                $keyTypes = $resultType->getKeyTypes();
                $valueTypes = $resultType->getValueTypes();

                foreach ($keyTypes as $i => $keyType) {
                    if (PDO::FETCH_NUM === $fetchType && $keyType instanceof ConstantIntegerType) {
                        $builder->setOffsetValueType($keyType, $valueTypes[$i]);
                    } elseif (PDO::FETCH_ASSOC === $fetchType && $keyType instanceof ConstantStringType) {
                        $builder->setOffsetValueType($keyType, $valueTypes[$i]);
                    }
                }

                if ('fetchAll' === $methodReflection->getName()) {
                    return new ArrayType(new IntegerType(), $builder->getArray());
                }

                // since php8 the default error mode changed to exception, therefore false returns are not longer possible
                if ($this->phpVersion->getVersionId() >= 80000) {
                    return $builder->getArray();
                }

                return new UnionType([$builder->getArray(), new ConstantBooleanType(false)]);
            }

            if ('fetchAll' === $methodReflection->getName()) {
                return new ArrayType(new IntegerType(), $resultType);
            }

            // since php8 the default error mode changed to exception, therefore false returns are not longer possible
            if ($this->phpVersion->getVersionId() >= 80000) {
                return $resultType;
            }

            return new UnionType([$resultType, new ConstantBooleanType(false)]);
        }

        return $defaultReturn;
    }
}
