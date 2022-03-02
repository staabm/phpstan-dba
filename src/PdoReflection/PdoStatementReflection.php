<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\PdoReflection;

use PDO;
use PDOStatement;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use staabm\PHPStanDba\QueryReflection\ExpressionFinder;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\QueryReflection\QueryReflector;

// XXX move into a "Reflection" package on next major version
final class PdoStatementReflection
{
    // XXX move into separate class on next major version
    public function findPrepareQueryStringExpression(MethodCall $methodCall): ?Expr
    {
        $exprFinder = new ExpressionFinder();
        $queryExpr = $exprFinder->findQueryStringExpression($methodCall);

        // resolve query parameter from "prepare"
        if ($queryExpr instanceof MethodCall) {
            $queryArgs = $queryExpr->getArgs();

            return $queryArgs[0]->value;
        }

        return null;
    }

    // XXX move into separate class on next major version
    /**
     * @return MethodCall[]
     */
    public function findPrepareBindCalls(MethodCall $methodCall): array
    {
        $exprFinder = new ExpressionFinder();

        return $exprFinder->findBindCalls($methodCall);
    }

    /**
     * Turns a PDO::FETCH_* parameter-type into a QueryReflector::FETCH_TYPE* constant.
     *
     * @return QueryReflector::FETCH_TYPE*|null
     */
    public function getFetchType(Type $fetchModeType): ?int
    {
        if (!$fetchModeType instanceof ConstantIntegerType) {
            return null;
        }

        if (PDO::FETCH_CLASS === $fetchModeType->getValue() || PDO::FETCH_OBJ === $fetchModeType->getValue()) {
            return QueryReflector::FETCH_TYPE_CLASS;
        } elseif (PDO::FETCH_KEY_PAIR === $fetchModeType->getValue()) {
            return QueryReflector::FETCH_TYPE_KEY_VALUE;
        } elseif (PDO::FETCH_ASSOC === $fetchModeType->getValue()) {
            return QueryReflector::FETCH_TYPE_ASSOC;
        } elseif (PDO::FETCH_NUM === $fetchModeType->getValue()) {
            return QueryReflector::FETCH_TYPE_NUMERIC;
        } elseif (PDO::FETCH_BOTH === $fetchModeType->getValue()) {
            return QueryReflector::FETCH_TYPE_BOTH;
        } elseif (PDO::FETCH_COLUMN === $fetchModeType->getValue()) {
            return QueryReflector::FETCH_TYPE_COLUMN;
        }

        return null;
    }

    /**
     * @param iterable<string>            $queryStrings
     * @param QueryReflector::FETCH_TYPE* $reflectionFetchType
     */
    public function createGenericStatement(iterable $queryStrings, int $reflectionFetchType): ?Type
    {
        $queryReflection = new QueryReflection();
        $genericObjects = [];

        foreach ($queryStrings as $queryString) {
            $bothType = $queryReflection->getResultType($queryString, QueryReflector::FETCH_TYPE_BOTH);

            if ($bothType) {
                $rowTypeInFetchMode = $this->reduceStatementResultType($bothType, $reflectionFetchType);

                $genericObjects[] = new GenericObjectType(PDOStatement::class, [$rowTypeInFetchMode, $bothType]);
            }
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
     * @param QueryReflector::FETCH_TYPE* $fetchType
     */
    public function modifyGenericStatement(GenericObjectType $statementType, int $fetchType): ?GenericObjectType
    {
        $genericTypes = $statementType->getTypes();

        if (2 !== \count($genericTypes)) {
            return null;
        }

        $bothType = $genericTypes[1];
        $rowTypeInFetchMode = $this->reduceStatementResultType($bothType, $fetchType);

        return new GenericObjectType(PDOStatement::class, [$rowTypeInFetchMode, $bothType]);
    }

    /**
     * @param QueryReflector::FETCH_TYPE* $fetchType
     */
    public function getRowType(Type $statementType, int $fetchType): ?Type
    {
        if ($statementType instanceof UnionType) {
            $rowTypes = [];

            foreach ($statementType->getTypes() as $type) {
                $rowType = $this->getRowType($type, $fetchType);
                if (null === $rowType) {
                    return null;
                }
                $rowTypes[] = $rowType;
            }

            return TypeCombinator::union(...$rowTypes);
        }

        if ($statementType instanceof GenericObjectType) {
            $genericTypes = $statementType->getTypes();

            if (2 !== \count($genericTypes)) {
                return null;
            }

            $bothType = $genericTypes[1];

            return $this->reduceStatementResultType($bothType, $fetchType);
        }

        return null;
    }

    public function getColumnRowType(Type $statementType, int $columnIndex): ?Type
    {
        $statementType = $this->getRowType($statementType, QueryReflector::FETCH_TYPE_NUMERIC);

        if ($statementType instanceof ConstantArrayType) {
            $valueTypes = $statementType->getValueTypes();
            if (\array_key_exists($columnIndex, $valueTypes)) {
                return $valueTypes[$columnIndex];
            }
        }

        return null;
    }

    /**
     * @param class-string $className
     */
    public function getClassRowType(Type $statementType, string $className): ?Type
    {
        return new ObjectType($className);
    }

    /**
     * @param QueryReflector::FETCH_TYPE* $fetchType
     */
    private function reduceStatementResultType(Type $bothType, int $fetchType): Type
    {
        if (!$bothType instanceof ConstantArrayType) {
            return $bothType;
        }

        if (\count($bothType->getValueTypes()) <= 0) {
            return $bothType;
        }

        // turn a BOTH typed statement into either NUMERIC or ASSOC
        if (
            QueryReflector::FETCH_TYPE_NUMERIC === $fetchType || QueryReflector::FETCH_TYPE_ASSOC === $fetchType
        ) {
            $builder = ConstantArrayTypeBuilder::createEmpty();

            $keyTypes = $bothType->getKeyTypes();
            $valueTypes = $bothType->getValueTypes();

            foreach ($keyTypes as $i => $keyType) {
                if (QueryReflector::FETCH_TYPE_NUMERIC === $fetchType && $keyType instanceof ConstantIntegerType) {
                    $builder->setOffsetValueType($keyType, $valueTypes[$i]);
                } elseif (QueryReflector::FETCH_TYPE_ASSOC === $fetchType && $keyType instanceof ConstantStringType) {
                    $builder->setOffsetValueType($keyType, $valueTypes[$i]);
                }
            }

            return $builder->getArray();
        }

        if (QueryReflector::FETCH_TYPE_CLASS === $fetchType) {
            return new ArrayType(new IntegerType(), new ObjectType('stdClass'));
        }

        // both types contains numeric and string keys, therefore the count is doubled
        if (QueryReflector::FETCH_TYPE_KEY_VALUE === $fetchType && \count($bothType->getValueTypes()) >= 4) {
            $valueTypes = $bothType->getValueTypes();

            return new ArrayType($valueTypes[0], $valueTypes[2]);
        }

        // not yet supported fetch type - or $fetchType == BOTH
        return $bothType;
    }
}
