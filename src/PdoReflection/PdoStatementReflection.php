<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\PdoReflection;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Type;
use staabm\PHPStanDba\QueryReflection\ExpressionFinder;
use staabm\PHPStanDba\QueryReflection\QueryReflector;

final class PdoStatementReflection
{
    public function findPrepareQueryStringExpression(MethodReflection $methodReflection, MethodCall $methodCall): ?Expr
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
     * @param QueryReflector::FETCH_TYPE* $fetchType
     *
     * @return Type|null
     */
    public function getStatementResultType(Type $statementType, int $fetchType)
    {
        if (!$statementType instanceof GenericObjectType) {
            return null;
        }

        $genericTypes = $statementType->getTypes();
        if (1 !== \count($genericTypes)) {
            return null;
        }

        $resultType = $genericTypes[0];
        if ((QueryReflector::FETCH_TYPE_NUMERIC === $fetchType || QueryReflector::FETCH_TYPE_ASSOC === $fetchType) && $resultType instanceof ConstantArrayType) {
            $builder = ConstantArrayTypeBuilder::createEmpty();

            $keyTypes = $resultType->getKeyTypes();
            $valueTypes = $resultType->getValueTypes();

            foreach ($keyTypes as $i => $keyType) {
                if (QueryReflector::FETCH_TYPE_NUMERIC === $fetchType && $keyType instanceof ConstantIntegerType) {
                    $builder->setOffsetValueType($keyType, $valueTypes[$i]);
                } elseif (QueryReflector::FETCH_TYPE_ASSOC === $fetchType && $keyType instanceof ConstantStringType) {
                    $builder->setOffsetValueType($keyType, $valueTypes[$i]);
                }
            }

            return $builder->getArray();
        }

        return $resultType;
    }
}
