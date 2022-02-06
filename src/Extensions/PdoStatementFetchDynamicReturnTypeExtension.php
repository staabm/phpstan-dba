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
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use staabm\PHPStanDba\PdoReflection\PdoStatementReflection;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\QueryReflection\QueryReflector;

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

        $reflectionFetchType = QueryReflection::getRuntimeConfiguration()->getDefaultFetchMode();
        if (\count($args) > 0) {
            $fetchModeType = $scope->getType($args[0]->value);
            if (!$fetchModeType instanceof ConstantIntegerType) {
                return null;
            }

            if (PDO::FETCH_ASSOC === $fetchModeType->getValue()) {
                $reflectionFetchType = QueryReflector::FETCH_TYPE_ASSOC;
            } elseif (PDO::FETCH_NUM === $fetchModeType->getValue()) {
                $reflectionFetchType = QueryReflector::FETCH_TYPE_NUMERIC;
            } elseif (PDO::FETCH_BOTH === $fetchModeType->getValue()) {
                $reflectionFetchType = QueryReflector::FETCH_TYPE_BOTH;
            } else {
                return null;
            }
        }

        $pdoStatementReflection = new PdoStatementReflection();
        $resultType = $pdoStatementReflection->getStatementResultType($statementType, $reflectionFetchType);
        if (null === $resultType) {
            return null;
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
