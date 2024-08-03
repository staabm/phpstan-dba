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
use PHPStan\Type\DynamicFunctionThrowTypeExtension;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use staabm\PHPStanDba\MysqliReflection\MysqliResultObjectType;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\QueryReflection\QueryReflector;
use staabm\PHPStanDba\UnresolvableQueryException;

final class MysqliQueryDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{
    /**
     * @var PhpVersion
     */
    private $phpVersion;

    public function __construct(PhpVersion $phpVersion)
    {
        $this->phpVersion = $phpVersion;
    }

    public function isFunctionSupported(FunctionReflection $functionReflection): bool
    {
        return in_array(strtolower($functionReflection->getName()), ['mysqli_fetch_assoc', 'mysqli_fetch_row', 'mysqli_fetch_object', 'mysqli_fetch_array'], true);
    }

    public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
    {
        $args = $functionCall->getArgs();
        $defaultReturn = ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();

        if (QueryReflection::getRuntimeConfiguration()->throwsMysqliExceptions($this->phpVersion)) {
            $defaultReturn = TypeCombinator::remove($defaultReturn, new ConstantBooleanType(false));
        }

        if (\count($args) < 1) {
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

    /**
     * @throws UnresolvableQueryException
     */
    private function inferResultType(Expr $mysqliExpr, Scope $scope): ?Type
    {
        $mysqliType = $scope->getType($mysqliExpr);

        if (!$mysqliType instanceof MysqliResultObjectType) {
            return null;
        }

        $queryReflection = new QueryReflection();
        $queryStrings = $queryReflection->resolveQueryStrings($mysqliExpr, $scope);

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

        if (!QueryReflection::getRuntimeConfiguration()->throwsMysqliExceptions($this->phpVersion)) {
            return TypeCombinator::union(
                $resultType,
                new ConstantBooleanType(false),
            );
        }

        return $resultType;
    }
}
