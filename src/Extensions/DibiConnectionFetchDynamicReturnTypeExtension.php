<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Extensions;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use staabm\PHPStanDba\DibiReflection\DibiReflection;
use staabm\PHPStanDba\DoctrineReflection\DoctrineReflection;
use staabm\PHPStanDba\DoctrineReflection\DoctrineResultObjectType;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\QueryReflection\QueryReflector;
use staabm\PHPStanDba\UnresolvableQueryException;

final class DibiConnectionFetchDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return \Dibi\Connection::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return \in_array($methodReflection->getName(), [
            'fetch',
            'fetchAll',
            'fetchPairs',
            'fetchSingle',
        ], true);
    }

    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): ?Type
    {
        $args = $methodCall->getArgs();

        if (\count($args) < 1) {
            return null;
        }

        if ($scope->getType($args[0]->value) instanceof MixedType) {
            return null;
        }

        try {
            return $this->inferType($methodReflection, $args[0]->value, $scope);
        } catch (UnresolvableQueryException $exception) {
            // simulation not possible.. use default value
        }

        return null;
    }

    private function inferType(MethodReflection $methodReflection, Expr $queryExpr, Scope $scope): ?Type
    {
        $queryReflection = new QueryReflection();
        $queryStrings = $queryReflection->resolveQueryStrings($queryExpr, $scope);

        return $this->createFetchType($queryStrings, $methodReflection);
    }

    /**
     * @param iterable<string> $queryStrings
     */
    private function createFetchType(iterable $queryStrings, MethodReflection $methodReflection): ?Type
    {
        $queryReflection = new QueryReflection();
        $dibiReflection = new DibiReflection();

        $fetchTypes = [];
        foreach ($queryStrings as $queryString) {
            $queryString = $dibiReflection->rewriteQuery($queryString);
            if ($queryString === null) {
                continue;
            }

            $resultType = $queryReflection->getResultType($queryString, QueryReflector::FETCH_TYPE_BOTH);

            if (null === $resultType) {
                return null;
            }

            $fetchResultType = $this->reduceResultType($methodReflection, $resultType);
            if (null === $fetchResultType) {
                return null;
            }

            $fetchTypes[] = $fetchResultType;
        }

        if (\count($fetchTypes) > 1) {
            return TypeCombinator::union(...$fetchTypes);
        }
        if (1 === \count($fetchTypes)) {
            return $fetchTypes[0];
        }

        return null;
    }

    private function reduceResultType(MethodReflection $methodReflection, Type $resultType): ?Type
    {
        if ($resultType instanceof UnionType) {
            $resultTypes = [];

            foreach ($resultType->getTypes() as $type) {
                $rowType = $this->reduceResultType($methodReflection, $type);
                if (null === $rowType) {
                    return null;
                }
                $resultTypes[] = $rowType;
            }

            return TypeCombinator::union(...$resultTypes);
        }

        $resultRowType = $resultType;
        $usedMethod = strtolower($methodReflection->getName());

        switch ($usedMethod) {
            case 'fetchPairs':
                $fetchType = QueryReflector::FETCH_TYPE_KEY_VALUE;
                break;
            case 'fetchSingle':
                $fetchType = QueryReflector::FETCH_TYPE_ONE;
                break;
            case 'fetchnumeric':
            case 'fetchallnumeric':
                $fetchType = QueryReflector::FETCH_TYPE_NUMERIC;
                break;
            case 'fetchAll':
                $fetchType = QueryReflector::FETCH_TYPE_ASSOC;
                break;
            default:
                $fetchType = QueryReflector::FETCH_TYPE_BOTH;
        }

        if (QueryReflector::FETCH_TYPE_BOTH !== $fetchType && $resultRowType instanceof ConstantArrayType) {
            $builder = ConstantArrayTypeBuilder::createEmpty();

            $keyTypes = $resultRowType->getKeyTypes();
            $valueTypes = $resultRowType->getValueTypes();

            if (QueryReflector::FETCH_TYPE_KEY_VALUE === $fetchType) {
                // $valueType contain 'BOTH' fetched values
                if (\count($valueTypes) < 4) {
                    return null;
                }

                if (\in_array($usedMethod, ['fetchallkeyvalue'], true)) {
                    return new ArrayType($valueTypes[0], $valueTypes[2]);
                }

                return new GenericObjectType(Traversable::class, [$valueTypes[0], $valueTypes[2]]);
            }

            foreach ($keyTypes as $i => $keyType) {
                if (QueryReflector::FETCH_TYPE_ONE === $fetchType) {
                    return TypeCombinator::union($valueTypes[$i], new ConstantBooleanType(false));
                }
                if (QueryReflector::FETCH_TYPE_FIRST_COL === $fetchType) {
                    return new ArrayType(IntegerRangeType::fromInterval(0, null), $valueTypes[$i]);
                }

                if (QueryReflector::FETCH_TYPE_NUMERIC === $fetchType && $keyType instanceof ConstantIntegerType) {
                    $builder->setOffsetValueType($keyType, $valueTypes[$i]);
                } elseif (QueryReflector::FETCH_TYPE_ASSOC === $fetchType && $keyType instanceof ConstantStringType) {
                    $builder->setOffsetValueType($keyType, $valueTypes[$i]);
                }
            }

            $resultType = $builder->getArray();

            if (\in_array($usedMethod, ['fetchAll'], true)) {
                return new ArrayType(IntegerRangeType::fromInterval(0, null), $resultType);
            }

            return $resultType;
        }

        return null;
    }
}
