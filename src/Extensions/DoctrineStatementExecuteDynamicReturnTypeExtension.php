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
use PHPStan\Reflection\ParametersAcceptorSelector;
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

        // make sure we don't report wrong types in doctrine 2.x
        if (!InstalledVersions::satisfies(new VersionParser(), 'doctrine/dbal', '3.*')) {
            return $defaultReturn;
        }

        try {
            $resultType = $this->inferType($methodReflection, $methodCall, $args[0]->value, $scope);
            if (null !== $resultType) {
                return $resultType;
            }
        } catch (UnresolvableQueryException $exception) {
            // simulation not possible.. use default value
        }

        return $defaultReturn;
    }

    private function inferType(MethodReflection $methodReflection, MethodCall $methodCall, Expr $paramsExpr, Scope $scope): ?Type
    {
        $doctrineReflection = new DoctrineReflection();

        $parameterTypes = $scope->getType($paramsExpr);

        $stmtReflection = new PdoStatementReflection();
        $queryExpr = $stmtReflection->findPrepareQueryStringExpression($methodCall);
        if (null === $queryExpr) {
            return null;
        }

        $queryReflection = new QueryReflection();
        $queryStrings = $queryReflection->resolvePreparedQueryStrings($queryExpr, $parameterTypes, $scope);

        return $doctrineReflection->createGenericResult($queryStrings, QueryReflector::FETCH_TYPE_BOTH);
    }
}
