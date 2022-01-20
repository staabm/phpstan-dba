<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Extensions;

use Composer\InstalledVersions;
use Composer\Semver\VersionParser;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Statement;
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
use staabm\PHPStanDba\UnresolvableQueryException;

final class DoctrineConnectionDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    /**
     * @var array<string, class-string> return types of Connection methods in doctrine 3.x
     */
    private $resultMap = [
        'query' => Result::class,
        //'prepare' => Statement::class,
    ];

    public function getClass(): string
    {
        return Connection::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return \in_array(strtolower($methodReflection->getName()), ['query'/*, 'prepare'*/], true);
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

        $resultType = $this->inferType($methodReflection, $args[0]->value, $scope);
        if (null !== $resultType) {
            return $resultType;
        }

        return $defaultReturn;
    }

    private function inferType(MethodReflection $methodReflection, Expr $queryExpr, Scope $scope): ?Type
    {
        $queryReflection = new QueryReflection();
        try {
            $queryString = $queryReflection->resolveQueryString($queryExpr, $scope);
            if (null === $queryString) {
                return null;
            }
        } catch (UnresolvableQueryException $e) {
            return null;
        }

        $genericMainType = $this->resultMap[strtolower($methodReflection->getName())];
        $resultType = $queryReflection->getResultType($queryString, QueryReflector::FETCH_TYPE_BOTH);
        if ($resultType) {
            return new GenericObjectType($genericMainType, [$resultType]);
        }

        return null;
    }
}
