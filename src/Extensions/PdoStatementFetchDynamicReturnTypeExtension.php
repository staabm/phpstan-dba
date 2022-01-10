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
use staabm\PHPStanDba\QueryReflection\QueryReflection;

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
        $defaultReturn = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();

        if (QueryReflection::getRuntimeConfiguration()->throwsPdoExceptions($this->phpVersion) && !$defaultReturn instanceof MixedType) {
            $defaultReturn = TypeCombinator::remove($defaultReturn, new ConstantBooleanType(false));
        }

        $resultType = $this->inferType($methodReflection, $methodCall, $scope);
        if (null !== $resultType) {
            return $resultType;
        }

        return $defaultReturn;
    }

    private function inferType(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): ?Type
    {
        $args = $methodCall->getArgs();
        $statementType = $scope->getType($methodCall->var);

        if (!$statementType instanceof GenericObjectType) {
            return null;
        }

        $genericTypes = $statementType->getTypes();

        if (1 !== \count($genericTypes)) {
            return null;
        }

        $fetchType = PDO::FETCH_BOTH;
        if (\count($args) > 0) {
            $fetchModeType = $scope->getType($args[0]->value);
            if (!$fetchModeType instanceof ConstantIntegerType) {
                return null;
            }
            $fetchType = $fetchModeType->getValue();

            if (!\in_array($fetchType, [PDO::FETCH_ASSOC, PDO::FETCH_NUM, PDO::FETCH_BOTH])) {
                return null;
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

            $resultType = $builder->getArray();
        }

        if ('fetchAll' === $methodReflection->getName()) {
            return new ArrayType(new IntegerType(), $resultType);
        }

        if (QueryReflection::getRuntimeConfiguration()->throwsPdoExceptions($this->phpVersion)) {
            return $resultType;
        }

        return new UnionType([$resultType, new ConstantBooleanType(false)]);
    }
}
