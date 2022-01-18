<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Rules;

use PDOStatement;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use staabm\PHPStanDba\PdoReflection\PdoStatementReflection;
use staabm\PHPStanDba\QueryReflection\PlaceholderValidation;
use staabm\PHPStanDba\QueryReflection\QueryReflection;

/**
 * @implements Rule<MethodCall>
 *
 * @see PdoStatementExecuteErrorMethodRuleTest
 */
final class PdoStatementExecuteMethodRule implements Rule
{
    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    public function processNode(Node $methodCall, Scope $scope): array
    {
        if (!$methodCall->name instanceof Node\Identifier) {
            return [];
        }

        $methodReflection = $scope->getMethodReflection($scope->getType($methodCall->var), $methodCall->name->toString());
        if (null === $methodReflection) {
            return [];
        }

        if (PdoStatement::class !== $methodReflection->getDeclaringClass()->getName()) {
            return [];
        }

        if ('execute' !== $methodReflection->getName()) {
            return [];
        }

        return $this->checkErrors($methodReflection, $methodCall, $scope);
    }

    /**
     * @return RuleError[]
     */
    private function checkErrors(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): array
    {
        $stmtReflection = new PdoStatementReflection();
        $queryExpr = $stmtReflection->findPrepareQueryStringExpression($methodReflection, $methodCall);

        if (null === $queryExpr) {
            return [];
        }

        $errors = [];
        $queryReflection = new QueryReflection();
        $placeholderReflection = new PlaceholderValidation();
        foreach ($queryReflection->resolveQueryStrings($queryExpr, $scope) as $queryString) {
            foreach ($placeholderReflection->checkErrors($queryString, $methodCall, $scope) as $error) {
                // make error messages unique
                $errors[$error] = $error;
            }
        }

        $ruleErrors = [];
        foreach ($errors as $error) {
            $ruleErrors[] = RuleErrorBuilder::message($error)->line($methodCall->getLine())->build();
        }

        return $ruleErrors;
    }
}
