<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\PdoReflection;

use PDO;
use PDOStatement;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Type;
use staabm\PHPStanDba\QueryReflection\ExpressionFinder;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\QueryReflection\QueryReflector;

final class PdoStatementReflection
{
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

        if (PDO::FETCH_ASSOC === $fetchModeType->getValue()) {
            return QueryReflector::FETCH_TYPE_ASSOC;
        } elseif (PDO::FETCH_NUM === $fetchModeType->getValue()) {
            return QueryReflector::FETCH_TYPE_NUMERIC;
        } elseif (PDO::FETCH_BOTH === $fetchModeType->getValue()) {
            return QueryReflector::FETCH_TYPE_BOTH;
        }

        return null;
    }

    /**
     * @param QueryReflector::FETCH_TYPE* $fetchType
     */
    public function createGenericStatement(string $queryString, int $fetchType): ?GenericObjectType
    {
        $queryReflection = new QueryReflection();
        $bothType = $queryReflection->getResultType($queryString, QueryReflector::FETCH_TYPE_BOTH);

        if ($bothType) {
            $rowTypeInFetchMode = $this->reduceStatementResultType($bothType, $fetchType);

            return new GenericObjectType(PDOStatement::class, [$rowTypeInFetchMode, $bothType]);
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
    public function getRowType(GenericObjectType $statementType, int $fetchType): ?Type
    {
        $genericTypes = $statementType->getTypes();

        if (2 !== \count($genericTypes)) {
            return null;
        }

        $bothType = $genericTypes[1];

        return $this->reduceStatementResultType($bothType, $fetchType);
    }

    /**
     * @param QueryReflector::FETCH_TYPE* $fetchType
     */
    private function reduceStatementResultType(Type $bothType, int $fetchType): Type
    {
        // turn a BOTH typed statement into either NUMERIC or ASSOC
        if (
            (QueryReflector::FETCH_TYPE_NUMERIC === $fetchType || QueryReflector::FETCH_TYPE_ASSOC === $fetchType) &&
            $bothType instanceof ConstantArrayType && \count($bothType->getValueTypes()) > 0
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

        // not yet supported fetch type - or $fetchType == BOTH
        return $bothType;
    }
}
