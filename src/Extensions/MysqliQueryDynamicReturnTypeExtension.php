<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Extensions;

use mysqli;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use staabm\PHPStanDba\MysqliReflection\MysqliResultObjectType;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\QueryReflection\QueryReflector;
use staabm\PHPStanDba\UnresolvableQueryException;

final class MysqliQueryDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension, DynamicFunctionReturnTypeExtension
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
        return mysqli::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return 'query' === $methodReflection->getName();
    }

    public function isFunctionSupported(FunctionReflection $functionReflection): bool
    {
        return 'mysqli_query' === $functionReflection->getName();
    }

    public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
    {
        $args = $functionCall->getArgs();
        $defaultReturn = ParametersAcceptorSelector::selectFromArgs(
            $scope,
            $functionCall->getArgs(),
            $functionReflection->getVariants()
        )->getReturnType();

        if (QueryReflection::getRuntimeConfiguration()->throwsMysqliExceptions($this->phpVersion)) {
            $defaultReturn = TypeCombinator::remove($defaultReturn, new ConstantBooleanType(false));
        }

        if (\count($args) < 2) {
            return $defaultReturn;
        }

        try {
            $resultType = $this->inferResultType($args[1]->value, $scope);
            if (null !== $resultType) {
                return $resultType;
            }
        } catch (UnresolvableQueryException $e) {
            // simulation not possible.. use default value
        }

        return $defaultReturn;
    }

    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type
    {
        $args = $methodCall->getArgs();
        $defaultReturn = ParametersAcceptorSelector::selectFromArgs(
            $scope,
            $methodCall->getArgs(),
            $methodReflection->getVariants()
        )->getReturnType();

        if (QueryReflection::getRuntimeConfiguration()->throwsMysqliExceptions($this->phpVersion)) {
            $defaultReturn = TypeCombinator::remove($defaultReturn, new ConstantBooleanType(false));
        }

        if (\count($args) < 1) {
            return $defaultReturn;
        }

        if ($scope->getType($args[0]->value) instanceof MixedType) {
            return $defaultReturn;
        }

        try {
            $resultType = $this->inferResultType($args[0]->value, $scope);
            if (null !== $resultType) {
                return $resultType;
            }
        } catch (UnresolvableQueryException $e) {
            // simulation not possible.. use default value
        }

        return $defaultReturn;
    }

    /**
     * @throws UnresolvableQueryException
     */
    private function inferResultType(Expr $queryExpr, Scope $scope): ?Type
    {
        $queryReflection = new QueryReflection();
        $queryStrings = $queryReflection->resolveQueryStrings($queryExpr, $scope);

        $genericObjects = [];
        foreach ($queryStrings as $queryString) {
            $resultType = $queryReflection->getResultType($queryString, QueryReflector::FETCH_TYPE_ASSOC);

            if (null === $resultType) {
                return null;
            }

            $genericObjects[] = new MysqliResultObjectType($resultType);
        }

        if (0 === \count($genericObjects)) {
            return null;
        }

        $resultType = TypeCombinator::union(...$genericObjects);

        if (! QueryReflection::getRuntimeConfiguration()->throwsMysqliExceptions($this->phpVersion)) {
            return TypeCombinator::union(
                $resultType,
                new ConstantBooleanType(false)
            );
        }

        return $resultType;
    }
}
