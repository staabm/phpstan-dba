<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\DoctrineReflection;

use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\QueryReflection\QueryReflector;
use Traversable;

// XXX move into a "Reflection" package on next major version
final class DoctrineReflection
{
    public function reduceResultType(MethodReflection $methodReflection, Type $resultType): ?Type
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

        if ($resultType instanceof DoctrineResultObjectType) {
            return $this->reduceResultType($methodReflection, $resultType->getRowType());
        }

        $usedMethod = strtolower($methodReflection->getName());

        $returnsList = false;
        switch ($usedMethod) {
            case 'fetchallkeyvalue':
            case 'iteratekeyvalue':
                $fetchType = QueryReflector::FETCH_TYPE_KEY_VALUE;
                break;
            case 'fetchone':
                $fetchType = QueryReflector::FETCH_TYPE_ONE;
                break;
            case 'fetchfirstcolumn':
                $returnsList = true;
                $fetchType = QueryReflector::FETCH_TYPE_FIRST_COL;
                break;
            case 'iteratecolumn':
                $fetchType = QueryReflector::FETCH_TYPE_FIRST_COL;
                break;
            case 'fetchnumeric':
            case 'fetchallnumeric':
                $returnsList = true;
                $fetchType = QueryReflector::FETCH_TYPE_NUMERIC;
                break;
            case 'iteratenumeric':
                $fetchType = QueryReflector::FETCH_TYPE_NUMERIC;
                break;
            case 'fetchallassociative':
                $returnsList = true;
                $fetchType = QueryReflector::FETCH_TYPE_ASSOC;
                break;
            case 'fetchassociative':
            case 'iterateassociative':
                $fetchType = QueryReflector::FETCH_TYPE_ASSOC;
                break;
            default:
                $fetchType = QueryReflector::FETCH_TYPE_BOTH;
        }

        $resultRowType = $resultType->getConstantArrays();
        if (QueryReflector::FETCH_TYPE_BOTH !== $fetchType && count($resultRowType) === 1) {
            $resultRowType = $resultRowType[0];
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
                    if (\in_array($usedMethod, ['iteratecolumn'], true)) {
                        return new GenericObjectType(Traversable::class, [new IntegerType(), $valueTypes[$i]]);
                    }

                    $arrayType = new ArrayType(IntegerRangeType::fromInterval(0, null), $valueTypes[$i]);
                    if ($returnsList) {
                        return TypeCombinator::intersect(
                            $arrayType,
                            new AccessoryArrayListType()
                        );
                    }
                    return $arrayType;
                }

                if (QueryReflector::FETCH_TYPE_NUMERIC === $fetchType && $keyType->isInteger()->yes()) {
                    $builder->setOffsetValueType($keyType, $valueTypes[$i]);
                } elseif (QueryReflector::FETCH_TYPE_ASSOC === $fetchType && $keyType->isString()->yes()) {
                    $builder->setOffsetValueType($keyType, $valueTypes[$i]);
                }
            }

            $resultType = $builder->getArray();

            if (\in_array($usedMethod, ['iterateassociative', 'iteratenumeric'], true)) {
                return new GenericObjectType(Traversable::class, [new IntegerType(), $resultType]);
            }

            if (\in_array($usedMethod, ['fetchallnumeric', 'fetchallassociative'], true)) {
                $arrayType = new ArrayType(IntegerRangeType::fromInterval(0, null), $resultType);
                if ($returnsList) {
                    return TypeCombinator::intersect(
                        $arrayType,
                        new AccessoryArrayListType()
                    );
                }
                return $arrayType;
            }

            if ($returnsList) {
                $resultType = TypeCombinator::intersect(
                    $resultType,
                    new AccessoryArrayListType()
                );
            }

            // false is returned if no rows are found.
            $resultType = TypeCombinator::union($resultType, new ConstantBooleanType(false));

            return $resultType;
        }

        return null;
    }

    /**
     * @param iterable<string>            $queryStrings
     * @param QueryReflector::FETCH_TYPE* $reflectionFetchType
     */
    public function createGenericStatement(iterable $queryStrings, int $reflectionFetchType): ?Type
    {
        $genericObjects = [];

        foreach ($queryStrings as $queryString) {
            $queryReflection = new QueryReflection();

            $resultType = $queryReflection->getResultType($queryString, $reflectionFetchType);
            if (null === $resultType) {
                return null;
            }

            $genericObjects[] = DoctrineStatementObjectType::newWithRowType($resultType);
        }

        if (\count($genericObjects) > 1) {
            return TypeCombinator::union(...$genericObjects);
        }
        if (1 === \count($genericObjects)) {
            return $genericObjects[0];
        }

        return null;
    }

    /**
     * @param iterable<string>            $queryStrings
     * @param QueryReflector::FETCH_TYPE* $reflectionFetchType
     */
    public function createGenericResult(iterable $queryStrings, int $reflectionFetchType): ?Type
    {
        $genericObjects = [];

        foreach ($queryStrings as $queryString) {
            $queryReflection = new QueryReflection();

            $resultType = $queryReflection->getResultType($queryString, $reflectionFetchType);
            if (null === $resultType) {
                return null;
            }

            $genericObjects[] = DoctrineResultObjectType::newWithRowType($resultType);
        }

        if (\count($genericObjects) > 1) {
            return TypeCombinator::union(...$genericObjects);
        }
        if (1 === \count($genericObjects)) {
            return $genericObjects[0];
        }

        return null;
    }

    /**
     * @param iterable<string> $queryStrings
     */
    public function createFetchType(iterable $queryStrings, MethodReflection $methodReflection): ?Type
    {
        $queryReflection = new QueryReflection();

        $fetchTypes = [];
        foreach ($queryStrings as $queryString) {
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
}
