<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Extensions;

use PhpParser\Node\Expr\StaticCall;
use rex;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\Type;

final class RexClassDynamicReturnTypeExtension implements DynamicStaticMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return rex::class;
    }

    public function isStaticMethodSupported(MethodReflection $methodReflection): bool
    {
        return in_array(strtolower($methodReflection->getName()), ['gettable', 'gettableprefix'], true);
    }

    public function getTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, Scope $scope): Type
    {
        $name = strtolower($methodReflection->getName());

        if ($name === "gettableprefix") {
            return new ConstantStringType('ada');
        }

        $args = $methodCall->getArgs();
        if (\count($args) < 1) {
            return ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();
        }

        $tableName = $scope->getType($args[0]->value);

        if ($tableName instanceof ConstantStringType) {
            return new ConstantStringType('rex_'. $tableName->getValue());
        }

        return ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();
    }
}
