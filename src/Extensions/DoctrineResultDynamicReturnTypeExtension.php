<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Extensions;

use Composer\InstalledVersions;
use Composer\Semver\VersionParser;
use Doctrine\DBAL\Result;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Type;
use staabm\PHPStanDba\DoctrineReflection\DoctrineReflection;

final class DoctrineResultDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    public const METHODS = [
        'fetchone',
        'columncount',
        'fetchfirstcolumn',
        'fetchnumeric',
        'fetchallnumeric',
        'fetchassociative',
        'fetchallassociative',
    ];

    public function getClass(): string
    {
        return Result::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return \in_array(strtolower($methodReflection->getName()), self::METHODS, true);
    }

    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type
    {
        $defaultReturn = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();

        // make sure we don't report wrong types in doctrine 2.x
        if (!InstalledVersions::satisfies(new VersionParser(), 'doctrine/dbal', '3.*')) {
            return $defaultReturn;
        }

        $resultType = $scope->getType($methodCall->var);
        if (!$resultType instanceof GenericObjectType) {
            return $defaultReturn;
        }

        $genericTypes = $resultType->getTypes();

        if (1 !== \count($genericTypes)) {
            return $defaultReturn;
        }

        $resultRowType = $genericTypes[0];

        if ('columncount' === strtolower($methodReflection->getName())) {
            if ($resultRowType instanceof ConstantArrayType) {
                $columnCount = \count($resultRowType->getKeyTypes()) / 2;
                if (!\is_int($columnCount)) {
                    throw new ShouldNotHappenException();
                }

                return new ConstantIntegerType($columnCount);
            }

            return $defaultReturn;
        }

        $doctrineReflection = new DoctrineReflection();
        $fetchResultType = $doctrineReflection->fetchResultType($methodReflection, $resultRowType);
        if (null !== $fetchResultType) {
            return $fetchResultType;
        }

        return $defaultReturn;
    }
}
