<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Extensions;

use PDO;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\UnresolvableQueryException;

final class PdoQuoteDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
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
        return PDO::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return 'quote' === $methodReflection->getName();
    }

    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type
    {
        $args = $methodCall->getArgs();
        $defaultReturn = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();

        if (QueryReflection::getRuntimeConfiguration()->throwsPdoExceptions($this->phpVersion)) {
            $defaultReturn = TypeCombinator::remove($defaultReturn, new ConstantBooleanType(false));
        }

        if (\count($args) < 1) {
            return $defaultReturn;
        }

        try {
            $stringType = $this->inferType($methodCall, $scope);
            if (null !== $stringType) {
                return $stringType;
            }
        } catch (UnresolvableQueryException $exception) {
            // simulation not possible.. use default value
        }

        return $defaultReturn;
    }

    private function inferType(MethodCall $methodCall, Scope $scope): ?Type
    {
        $args = $methodCall->getArgs();

        if (1 === \count($args)) {
            $type = PDO::PARAM_STR;
        } else {
            $typeType = $scope->getType($args[1]->value);
            if (!$typeType instanceof ConstantIntegerType) {
                return null;
            }
            $type = $typeType->getValue();
        }

        $argType = $scope->getType($args[0]->value);
        $stringType = $this->inferStringType($argType);

        // check for types which are supported by all drivers, therefore cannot return false.
        if (PDO::PARAM_STR === $type || PDO::PARAM_INT === $type || PDO::PARAM_BOOL === $type) {
            return $stringType;
        }

        if (QueryReflection::getRuntimeConfiguration()->throwsPdoExceptions($this->phpVersion)) {
            return $stringType;
        }

        return TypeCombinator::union($stringType, new ConstantBooleanType(false));
    }

    private function inferStringType(Type $argType): Type
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
