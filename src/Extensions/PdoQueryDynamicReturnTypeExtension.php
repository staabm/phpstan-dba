<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Extensions;

use PDO;
use PDOStatement;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
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
        $mixed = new MixedType(true);

        $defaultReturn = TypeCombinator::union(
            new GenericObjectType(PDOStatement::class, [new ArrayType($mixed, $mixed)]),
            new ConstantBooleanType(false)
        );

        // since php8 the default error mode changed to exception, therefore false returns are not longer possible
        if ($this->phpVersion->getVersionId() >= 80000) {
            TypeCombinator::remove($defaultReturn, new ConstantBooleanType(false));
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
