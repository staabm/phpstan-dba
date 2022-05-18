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
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use staabm\PHPStanDba\DoctrineReflection\DoctrineReflection;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\QueryReflection\QueryReflector;

final class DoctrineConnectionExecuteQueryDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return Connection::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return \in_array(strtolower($methodReflection->getName()), ['executequery', 'executecachequery'], true);
    }

    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type
    {
        $args = $methodCall->getArgs();
        $defaultReturn = ParametersAcceptorSelector::selectFromArgs(
            $scope,
            $methodCall->getArgs(),
            $methodReflection->getVariants(),
        )->getReturnType();

        if (\count($args) < 1) {
            return $defaultReturn;
        }

        if ($scope->getType($args[0]->value) instanceof MixedType) {
            return $defaultReturn;
        }

        // make sure we don't report wrong types in doctrine 2.x
        if (!InstalledVersions::satisfies(new VersionParser(), 'doctrine/dbal', '3.*')) {
            return $defaultReturn;
        }

        $params = null;
        $paramTypes = null;
        if (\count($args) > 1) {
            $params = $args[1]->value;
        }
        if (\count($args) > 2) {
            $paramTypes = $args[2]->value;
        }

        $resultType = $this->inferType($args[0]->value, $params, $paramTypes, $scope);
        if (null !== $resultType) {
            return $resultType;
        }

        return $defaultReturn;
    }

    private function inferType(Expr $queryExpr, ?Expr $paramsExpr, ?Expr $paramTypesExpr, Scope $scope): ?Type
    {
        if (null === $paramsExpr) {
            $queryReflection = new QueryReflection();
            $queryStrings = $queryReflection->resolveQueryStrings($queryExpr, $scope);
        } else {
            $parameterTypes = $scope->getType($paramsExpr);
            $boundParameterTypes = $scope->getType($paramsExpr);

            $queryReflection = new QueryReflection();
            $queryStrings = $queryReflection->resolvePreparedQueryStrings($queryExpr, $parameterTypes, $boundParameterTypes, $scope);
        }

        $doctrineReflection = new DoctrineReflection();

        return $doctrineReflection->createGenericResult($queryStrings, QueryReflector::FETCH_TYPE_BOTH);
    }
}
