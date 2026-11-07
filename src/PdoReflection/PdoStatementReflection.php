<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\PdoReflection;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use staabm\PHPStanDba\Ast\ExpressionFinder;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\QueryReflection\QueryReflector;

// XXX move into a "Reflection" package on next major version
final class PdoStatementReflection
{
    // Values of PDO::FETCH_* constants, independent of the loaded extensions.
    private const FETCH_ASSOC = 2;

    private const FETCH_NUM = 3;

    private const FETCH_BOTH = 4;

    private const FETCH_OBJ = 5;

    private const FETCH_COLUMN = 7;

    private const FETCH_CLASS = 8;

    private const FETCH_KEY_PAIR = 12;

    // XXX move into separate class on next major version
    public function findPrepareQueryStringExpression(MethodCall $methodCall): ?Expr
    {
        $exprFinder = new ExpressionFinder();
        $queryExpr = $exprFinder->findAssignmentExpression($methodCall);

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
        if (! $fetchModeType instanceof ConstantIntegerType) {
            return null;
        }

        if (self::FETCH_CLASS === $fetchModeType->getValue() || self::FETCH_OBJ === $fetchModeType->getValue()) {
            return QueryReflector::FETCH_TYPE_CLASS;
        } elseif (self::FETCH_KEY_PAIR === $fetchModeType->getValue()) {
            return QueryReflector::FETCH_TYPE_KEY_VALUE;
        } elseif (self::FETCH_ASSOC === $fetchModeType->getValue()) {
            return QueryReflector::FETCH_TYPE_ASSOC;
        } elseif (self::FETCH_NUM === $fetchModeType->getValue()) {
            return QueryReflector::FETCH_TYPE_NUMERIC;
        } elseif (self::FETCH_BOTH === $fetchModeType->getValue()) {
            return QueryReflector::FETCH_TYPE_BOTH;
        } elseif (self::FETCH_COLUMN === $fetchModeType->getValue()) {
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

            if (null !== $bothType) {
                $genericObjects[] = PdoStatementObjectType::newWithBothAndFetchType($bothType, $reflectionFetchType);
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

        if ($statementType instanceof PdoStatementObjectType) {
            return $statementType->newWithFetchType($fetchType)->getRowType();
        }

        return null;
    }

    public function getColumnRowType(Type $statementType, int $columnIndex): ?Type
    {
        $statementType = $this->getRowType($statementType, QueryReflector::FETCH_TYPE_NUMERIC);
        if ($statementType === null) {
            return null;
        }

        $arrays = $statementType->getConstantArrays();
        if (count($arrays) === 1) {
            $statementType = $arrays[0];

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
    public function getClassRowType(Type $statementType, string $className): Type
    {
        return new ObjectType($className);
    }
}
