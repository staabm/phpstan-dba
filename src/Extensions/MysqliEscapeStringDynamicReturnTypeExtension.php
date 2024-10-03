<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Extensions;

use mysqli;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use staabm\PHPStanDba\QueryReflection\QueryReflection;

final class MysqliEscapeStringDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension, DynamicFunctionReturnTypeExtension
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
        return 'real_escape_string' === $methodReflection->getName();
    }

    public function isFunctionSupported(FunctionReflection $functionReflection): bool
    {
        return 'mysqli_real_escape_string' === $functionReflection->getName();
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

        $argType = $scope->getType($args[1]->value);

        return $this->inferType($argType);
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

        if (0 === \count($args)) {
            return $defaultReturn;
        }

        $argType = $scope->getType($args[0]->value);

        return $this->inferType($argType);
    }

    private function inferType(Type $argType): Type
    {
        $intersection = [new StringType()];

        if ($argType->isNumericString()->yes()) {
            // a numeric string is by definition non-empty. therefore don't combine the 2 accessories
            $intersection[] = new AccessoryNumericStringType();
        } elseif ($argType->isNonEmptyString()->yes()) {
            $intersection[] = new AccessoryNonEmptyStringType();
        }

        if (\count($intersection) > 1) {
            return new IntersectionType($intersection);
        }

        return new StringType();
    }
}
