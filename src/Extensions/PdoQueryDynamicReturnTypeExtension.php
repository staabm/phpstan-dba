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
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Type;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\QueryReflection\QueryReflector;

final class PdoQueryDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
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
        return PDO::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return 'query' === $methodReflection->getName();
    }

    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type
    {
        $args = $methodCall->getArgs();
        $defaultReturn = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();

        if (QueryReflection::getRuntimeConfiguration()->throwsPdoExceptions($this->phpVersion)) {
            $defaultReturn = TypeCombinator::remove($defaultReturn, new ConstantBooleanType(false));
        }

        if (\count($args) < 1) {
            return $defaultReturn;
        }

        $reflectionFetchType = QueryReflector::FETCH_TYPE_BOTH;
        if (\count($args) >= 2) {
            $fetchModeType = $scope->getType($args[1]->value);
            if (!$fetchModeType instanceof ConstantIntegerType) {
                return $defaultReturn;
            }

            if (PDO::FETCH_ASSOC === $fetchModeType->getValue()) {
                $reflectionFetchType = QueryReflector::FETCH_TYPE_ASSOC;
            } elseif (PDO::FETCH_NUM === $fetchModeType->getValue()) {
                $reflectionFetchType = QueryReflector::FETCH_TYPE_NUMERIC;
            } elseif (PDO::FETCH_BOTH === $fetchModeType->getValue()) {
                $reflectionFetchType = QueryReflector::FETCH_TYPE_BOTH;
            } else {
                return $defaultReturn;
            }
        }

        $queryReflection = new QueryReflection();
        $queryString = $queryReflection->resolveQueryString($args[0]->value, $scope);
        if (null === $queryString) {
            return $defaultReturn;
        }

        $resultType = $queryReflection->getResultType($queryString, $reflectionFetchType);
        if ($resultType) {
            return new GenericObjectType(PDOStatement::class, [$resultType]);
        }

        return $defaultReturn;
    }
}
