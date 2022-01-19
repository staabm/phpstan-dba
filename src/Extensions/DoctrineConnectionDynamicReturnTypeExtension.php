<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Extensions;

use Composer\InstalledVersions;
use Composer\Semver\VersionParser;
use Doctrine\DBAL\Driver\Connection;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Type;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\QueryReflection\QueryReflector;

final class DoctrineConnectionDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return Connection::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return 'query' === $methodReflection->getName();
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

        $resultType = $this->inferType($methodCall, $args[0]->value, $scope);

        // make sure we don't report wrong types in doctrine 2.x
        if (InstalledVersions::satisfies(new VersionParser(), 'doctrine/dbal', '2.*')) {
            return $defaultReturn;
        }

        if (null !== $resultType) {
            return $resultType;
        }

        return $defaultReturn;
    }

    private function inferType(MethodCall $methodCall, Expr $queryExpr, Scope $scope): ?Type
    {
        $queryReflection = new QueryReflection();
        $queryString = $queryReflection->resolveQueryString($queryExpr, $scope);
        if (null === $queryString) {
            return null;
        }

        $resultType = $queryReflection->getResultType($queryString, QueryReflector::FETCH_TYPE_BOTH);
        if ($resultType) {
            return new GenericObjectType(Result::class, [$resultType]);
        }

        return null;
    }
}
