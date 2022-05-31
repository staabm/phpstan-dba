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
use PHPStan\Type\Type;
use staabm\PHPStanDba\DoctrineReflection\DoctrineReflection;
use staabm\PHPStanDba\DoctrineReflection\DoctrineResultObjectType;
use staabm\PHPStanDba\UnresolvableQueryException;

final class DoctrineResultDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    private const METHODS = [
        'columncount',
        'fetchone',
        'fetchfirstcolumn',
        'fetchnumeric',
        'fetchallnumeric',
        'fetchassociative',
        'fetchallassociative',
        'fetchallkeyvalue',
        'iteratenumeric',
        'iterateassociative',
        'iteratecolumn',
        'iteratekeyvalue',
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

        try {
            $resultType = $this->inferType($methodReflection, $methodCall, $scope);
            if (null !== $resultType) {
                return $resultType;
            }
        } catch (UnresolvableQueryException $exception) {
            // simulation not possible.. use default value
        }

        return $defaultReturn;
    }

    private function inferType(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): ?Type
    {
        $resultType = $scope->getType($methodCall->var);
        if ('columncount' === strtolower($methodReflection->getName())) {
            if ($resultType instanceof DoctrineResultObjectType) {
                $resultRowType = $resultType->getRowType();

                if ($resultRowType instanceof ConstantArrayType) {
                    $columnCount = \count($resultRowType->getKeyTypes()) / 2;
                    if (!\is_int($columnCount)) {
                        throw new ShouldNotHappenException();
                    }

                    return new ConstantIntegerType($columnCount);
                }
            }

            return null;
        }

        $doctrineReflection = new DoctrineReflection();

        return $doctrineReflection->reduceResultType($methodReflection, $resultType);
    }
}
