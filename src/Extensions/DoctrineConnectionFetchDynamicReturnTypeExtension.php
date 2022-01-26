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

final class DoctrineConnectionFetchDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    private const METHODS = [
        'fetchone',
        'fetchfirstcolumn',
        'fetchassociative',
        'fetchallassociative',
        'fetchnumeric',
        'fetchallnumeric',
        'fetchallkeyvalue',
        'iteratecolumn',
        'iterateassociative',
        'iteratenumeric',
        'iteratekeyvalue',
    ];

    public function getClass(): string
    {
        return Connection::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return \in_array(strtolower($methodReflection->getName()), self::METHODS, true);
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

        $resultType = $queryReflection->getResultType($queryString, QueryReflector::FETCH_TYPE_BOTH);

        if ($resultType) {
            $doctrineReflection = new DoctrineReflection();
            $fetchResultType = $doctrineReflection->fetchResultType($methodReflection, $resultType);

            if (null !== $fetchResultType) {
                return $fetchResultType;
            }
        }

        return null;
    }
}
