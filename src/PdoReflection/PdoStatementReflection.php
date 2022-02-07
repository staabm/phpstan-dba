<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\PdoReflection;

use PDO;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Type;
use staabm\PHPStanDba\QueryReflection\ExpressionFinder;

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
     * @param PDO::FETCH* $fetchType
     */
    public function getStatementResultType(Type $statementType, int $fetchType): ?Type
    {
        if (!$statementType instanceof GenericObjectType) {
            return null;
        }

        $genericTypes = $statementType->getTypes();
        if (1 !== \count($genericTypes)) {
            return null;
        }

        $resultType = $genericTypes[0];
        if ((PDO::FETCH_NUM === $fetchType || PDO::FETCH_ASSOC === $fetchType) &&
            $resultType instanceof ConstantArrayType && \count($resultType->getValueTypes()) > 0) {
            $builder = ConstantArrayTypeBuilder::createEmpty();

            $keyTypes = $resultType->getKeyTypes();
            $valueTypes = $resultType->getValueTypes();

            foreach ($keyTypes as $i => $keyType) {
                if (PDO::FETCH_NUM === $fetchType && $keyType instanceof ConstantIntegerType) {
                    $builder->setOffsetValueType($keyType, $valueTypes[$i]);
                } elseif (PDO::FETCH_ASSOC === $fetchType && $keyType instanceof ConstantStringType) {
                    $builder->setOffsetValueType($keyType, $valueTypes[$i]);
                }
            }

            return $builder->getArray();
        }

        return $resultType;
    }
}
