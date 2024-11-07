<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Extensions;

use PDOStatement;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use staabm\PHPStanDba\UnresolvableQueryException;

final class PdoStatementFetchObjectDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    private ReflectionProvider $reflectionProvider;

    public function __construct(ReflectionProvider $reflectionProvider)
    {
        $this->reflectionProvider = $reflectionProvider;
    }

    public function getClass(): string
    {
        return PDOStatement::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return \in_array($methodReflection->getName(), ['fetchObject'], true);
    }

    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): ?Type
    {
        try {
            return $this->inferType($methodReflection, $methodCall, $scope);
        } catch (UnresolvableQueryException $exception) {
            // simulation not possible.. use default value
        }

        return null;
    }

    private function inferType(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): ?Type
    {
        $args = $methodCall->getArgs();

        $className = 'stdClass';

        if (\count($args) >= 1) {
            $classStringType = $scope->getType($args[0]->value);
            $strings = $classStringType->getConstantStrings();
            if (count($strings) === 1) {
                $className = $strings[0]->getValue();
            } else {
                return null;
            }
        }

        if (! $this->reflectionProvider->hasClass($className)) {
            // XXX should we return NEVER or FALSE on unknown classes?
            return null;
        }

        $classString = $this->reflectionProvider->getClass($className)->getName();

        return TypeCombinator::union(new ObjectType($classString), new ConstantBooleanType(false));
    }
}
