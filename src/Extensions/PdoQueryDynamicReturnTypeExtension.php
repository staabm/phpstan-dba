<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Extensions;

use PDO;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use staabm\PHPStanDba\PdoReflection\PdoStatementObjectType;
use staabm\PHPStanDba\PdoReflection\PdoStatementReflection;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\QueryReflection\QueryReflector;
use staabm\PHPStanDba\UnresolvableQueryException;

final class PdoQueryDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    private PhpVersion $phpVersion;

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
        $defaultReturn = ParametersAcceptorSelector::selectFromArgs(
            $scope,
            $methodCall->getArgs(),
            $methodReflection->getVariants()
        )->getReturnType();

        if (QueryReflection::getRuntimeConfiguration()->throwsPdoExceptions($this->phpVersion)) {
            $defaultReturn = TypeCombinator::remove($defaultReturn, new ConstantBooleanType(false));
        }

        if (\count($args) < 1) {
            return $defaultReturn;
        }

        if ($scope->getType($args[0]->value) instanceof MixedType) {
            return $defaultReturn;
        }

        try {
            $resultType = $this->inferType($methodCall, $args[0]->value, $scope);
            if (null !== $resultType) {
                return $resultType;
            }
        } catch (UnresolvableQueryException $exception) {
            // simulation not possible.. use default value
        }

        return $defaultReturn;
    }

    /**
     * @throws UnresolvableQueryException
     */
    private function inferType(MethodCall $methodCall, Expr $queryExpr, Scope $scope): ?Type
    {
        $args = $methodCall->getArgs();
        $pdoStatementReflection = new PdoStatementReflection();

        $reflectionFetchType = QueryReflection::getRuntimeConfiguration()->getDefaultFetchMode();
        if (\count($args) >= 2) {
            $fetchModeType = $scope->getType($args[1]->value);

            $reflectionFetchType = $pdoStatementReflection->getFetchType($fetchModeType);
            if (null === $reflectionFetchType) {
                return null;
            }
            // not yet implemented on query()
            if (QueryReflector::FETCH_TYPE_COLUMN === $reflectionFetchType) {
                return null;
            }
        }

        $queryReflection = new QueryReflection();
        $queryStrings = $queryReflection->resolveQueryStrings($queryExpr, $scope);

        $pdoStatementReflection = new PdoStatementReflection();
        $genericStatement = $pdoStatementReflection->createGenericStatement($queryStrings, $reflectionFetchType);

        if (null !== $genericStatement) {
            return $genericStatement;
        }

        $queryStrings = $queryReflection->resolveQueryStrings($queryExpr, $scope);
        foreach ($queryStrings as $queryString) {
            if ('SELECT' !== QueryReflection::getQueryType($queryString)) {
                return null;
            }

            // return unknown type if query contains syntax errors
            if (null !== $queryReflection->validateQueryString($queryString)) {
                return null;
            }
        }

        return PdoStatementObjectType::createDefaultType($reflectionFetchType);
    }
}
