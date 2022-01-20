<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\PdoReflection;

use PDOStatement;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Reflection\MethodReflection;
use PHPStan\ShouldNotHappenException;
use staabm\PHPStanDba\QueryReflection\ExpressionFinder;

final class PdoStatementReflection
{
    public function findPrepareQueryStringExpression(MethodReflection $methodReflection, MethodCall $methodCall): ?Expr
    {
        if ('execute' !== $methodReflection->getName() || PdoStatement::class !== $methodReflection->getDeclaringClass()->getName()) {
            throw new ShouldNotHappenException();
        }

        $exprFinder = new ExpressionFinder();
        $queryExpr = $exprFinder->findQueryStringExpression($methodCall);

        // resolve query parameter from "prepare"
        if ($queryExpr instanceof MethodCall) {
            $queryArgs = $queryExpr->getArgs();

            return $queryArgs[0]->value;
        }

        return null;
    }
}
