<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Extensions;

use Composer\InstalledVersions;
use Composer\Semver\VersionParser;
use Doctrine\DBAL\Connection;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\QueryReflection\QueryReflector;
use Traversable;

final class DoctrineConnectionFetchDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return Connection::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return \in_array(strtolower($methodReflection->getName()), ['fetchassociative', 'fetchnumeric', 'iterateassociative', 'iteratenumeric'], true);
    }

    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type
    {
        $args = $methodCall->getArgs();
        $defaultReturn = ParametersAcceptorSelector::selectFromArgs(
            $scope,
            $methodCall->getArgs(),
            $methodReflection->getVariants(),
        )->getReturnType();

        if (\count($args) < 2) {
            return $defaultReturn;
        }

        if ($scope->getType($args[0]->value) instanceof MixedType) {
            return $defaultReturn;
        }

        // make sure we don't report wrong types in doctrine 2.x
        if (!InstalledVersions::satisfies(new VersionParser(), 'doctrine/dbal', '3.*')) {
            return $defaultReturn;
        }

        $resultType = $this->inferType($methodReflection, $args[0]->value, $args[1]->value, $scope);
        if (null !== $resultType) {
            return $resultType;
        }

        return $defaultReturn;
    }

    private function inferType(MethodReflection $methodReflection, Expr $queryExpr, Expr $paramsExpr, Scope $scope): ?Type
    {
        $parameterTypes = $scope->getType($paramsExpr);

        $queryReflection = new QueryReflection();
        $queryString = $queryReflection->resolvePreparedQueryString($queryExpr, $parameterTypes, $scope);
        if (null === $queryString) {
            return null;
        }

        $fetchType = QueryReflector::FETCH_TYPE_BOTH;
        $usedMethod = strtolower($methodReflection->getName());
        switch ($usedMethod) {
            case 'fetchassociative':
            case 'iterateassociative':
                $fetchType = QueryReflector::FETCH_TYPE_ASSOC;
                break;
            case 'fetchnumeric':
            case 'iteratenumeric':
                $fetchType = QueryReflector::FETCH_TYPE_NUMERIC;
                break;
        }

        $resultType = $queryReflection->getResultType($queryString, $fetchType);

        if ($resultType) {
            if (\in_array($usedMethod, ['iterateassociative', 'iteratenumeric'], true)) {
                return new GenericObjectType(Traversable::class, [new IntegerType(), $resultType]);
            }

            // false is returned if no rows are found.
            $resultType = TypeCombinator::union($resultType, new ConstantBooleanType(false));

            return $resultType;
        }

        return null;
    }
}
