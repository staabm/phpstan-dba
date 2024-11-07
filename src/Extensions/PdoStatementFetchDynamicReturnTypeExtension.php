<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Extensions;

use PDOStatement;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use staabm\PHPStanDba\PdoReflection\PdoStatementReflection;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\QueryReflection\QueryReflector;
use staabm\PHPStanDba\UnresolvableQueryException;

final class PdoStatementFetchDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    private PhpVersion $phpVersion;

    private ReflectionProvider $reflectionProvider;

    public function __construct(PhpVersion $phpVersion, ReflectionProvider $reflectionProvider)
    {
        $this->phpVersion = $phpVersion;
        $this->reflectionProvider = $reflectionProvider;
    }

    public function getClass(): string
    {
        return PDOStatement::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return \in_array($methodReflection->getName(), ['fetchAll', 'fetch'], true);
    }

    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type
    {
        $returnType = ParametersAcceptorSelector::selectFromArgs(
            $scope,
            $methodCall->getArgs(),
            $methodReflection->getVariants()
        )->getReturnType();

        try {
            $resultType = $this->inferType($methodReflection, $methodCall, $scope);
            if (null !== $resultType) {
                $returnType = $resultType;
            }
        } catch (UnresolvableQueryException $exception) {
            // simulation not possible.. use default value
        }

        // fetchAll() can return false prior to php8
        if (! $returnType instanceof MixedType && 'fetchAll' === $methodReflection->getName() && $this->phpVersion->getVersionId() >= 80000) {
            $returnType = TypeCombinator::remove($returnType, new ConstantBooleanType(false));
        }

        return $returnType;
    }

    private function inferType(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): ?Type
    {
        $args = $methodCall->getArgs();
        $pdoStatementReflection = new PdoStatementReflection();

        $statementType = $scope->getType($methodCall->var);
        $fetchType = QueryReflection::getRuntimeConfiguration()->getDefaultFetchMode();

        if (\count($args) > 0) {
            $fetchModeType = $scope->getType($args[0]->value);
            $fetchType = $pdoStatementReflection->getFetchType($fetchModeType);

            if (null === $fetchType) {
                return null;
            }
        }

        if (QueryReflector::FETCH_TYPE_COLUMN === $fetchType) {
            $columnIndex = 0;

            if (\count($args) > 1) {
                $columnIndexType = $scope->getType($args[1]->value);
                if ($columnIndexType instanceof ConstantIntegerType) {
                    $columnIndex = $columnIndexType->getValue();
                } else {
                    return null;
                }
            }

            $rowType = $pdoStatementReflection->getColumnRowType($statementType, $columnIndex);
        } elseif (QueryReflector::FETCH_TYPE_CLASS === $fetchType) {
            $className = 'stdClass';

            if (\count($args) > 1) {
                $classStringType = $scope->getType($args[1]->value);
                $strings = $classStringType->getConstantStrings();
                if (count($strings) === 1) {
                    $className = $strings[0]->getValue();
                } else {
                    return null;
                }
            }

            if (! $this->reflectionProvider->hasClass($className)) {
                return null;
            }

            $classString = $this->reflectionProvider->getClass($className)->getName();

            $rowType = $pdoStatementReflection->getClassRowType($statementType, $classString);
        } else {
            $rowType = $pdoStatementReflection->getRowType($statementType, $fetchType);
        }

        if (null === $rowType) {
            return null;
        }

        if ('fetchAll' === $methodReflection->getName()) {
            if (QueryReflector::FETCH_TYPE_KEY_VALUE === $fetchType) {
                return $rowType;
            }

            return new ArrayType(new IntegerType(), $rowType);
        }

        return TypeCombinator::union($rowType, new ConstantBooleanType(false));
    }
}
