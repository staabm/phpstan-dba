<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Extensions;

use Composer\InstalledVersions;
use Composer\Semver\VersionParser;
use Doctrine\DBAL\Statement;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Type;
use staabm\PHPStanDba\DoctrineReflection\DoctrineReflection;
use staabm\PHPStanDba\PdoReflection\PdoStatementReflection;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\QueryReflection\QueryReflector;
use staabm\PHPStanDba\UnresolvableQueryException;

final class DoctrineStatementExecuteDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return Statement::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return \in_array(strtolower($methodReflection->getName()), ['executequery', 'execute'], true);
    }

    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): ?Type
    {
        $args = $methodCall->getArgs();

        if (\count($args) < 1) {
            return null;
        }

        // make sure we don't report wrong types in doctrine 2.x
        if (! InstalledVersions::satisfies(new VersionParser(), 'doctrine/dbal', '3.*|4.*')) {
            return null;
        }

        try {
            return $this->inferType($methodReflection, $methodCall, $args[0]->value, $scope);
        } catch (UnresolvableQueryException $exception) {
            // simulation not possible.. use default value
        }

        return null;
    }

    private function inferType(MethodReflection $methodReflection, MethodCall $methodCall, Expr $paramsExpr, Scope $scope): ?Type
    {
        $doctrineReflection = new DoctrineReflection();
        $stmtReflection = new PdoStatementReflection();

        $queryExpr = $stmtReflection->findPrepareQueryStringExpression($methodCall);
        if (null === $queryExpr) {
            return null;
        }

        $queryReflection = new QueryReflection();
        $parameterTypes = $queryReflection->resolveParameterTypes($paramsExpr, $scope);
        $queryStrings = $queryReflection->resolvePreparedQueryStrings($queryExpr, $parameterTypes, $scope);

        return $doctrineReflection->createGenericResult($queryStrings, QueryReflector::FETCH_TYPE_BOTH);
    }
}
