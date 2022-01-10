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
use PHPStan\ShouldNotHappenException;
use staabm\PHPStanDba\PdoReflection\PdoStatementReflection;
use staabm\PHPStanDba\QueryReflection\QueryReflection;

/**
 * @implements Rule<MethodCall>
 *
 * @see PdoStatementExecuteErrorMethodRuleTest
 */
final class PdoStatementExecuteErrorMethodRule implements Rule
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

        $queryReflection = new QueryReflection();
        $queryString = $queryReflection->resolveQueryString($queryExpr, $scope);
        if (null === $queryString) {
            return [];
        }

        $args = $methodCall->getArgs();
        $placeholderCount = $this->countPlaceholders($queryString);

        if (0 === \count($args)) {
            if (0 === $placeholderCount) {
                return [];
            }

            return [
                RuleErrorBuilder::message(sprintf('Query expects %s placeholders, but no values are given to execute().', $placeholderCount))->line($methodCall->getLine())->build(),
            ];
        }

        $parameterTypes = $scope->getType($args[0]->value);
        $parameters = $queryReflection->resolveParameters($parameterTypes);
        if (null === $parameters) {
            return [];
        }
        $parameterCount = \count($parameters);

        if ($parameterCount !== $placeholderCount) {
            if (1 === $parameterCount) {
                return [
                    RuleErrorBuilder::message(sprintf('Query expects %s placeholders, but %s value is given to execute().', $placeholderCount, $parameterCount))->line($methodCall->getLine())->build(),
                ];
            }

            return [
                RuleErrorBuilder::message(sprintf('Query expects %s placeholders, but %s values are given to execute().', $placeholderCount, $parameterCount))->line($methodCall->getLine())->build(),
            ];
        }

        return [];
    }

    /**
     * @return 0|positive-int
     */
    private function countPlaceholders(string $queryString): int
    {
        $numPlaceholders = substr_count($queryString, '?');

        if (0 !== $numPlaceholders) {
            return $numPlaceholders;
        }

        $numPlaceholders = preg_match_all('{:[a-z]+}', $queryString, $matches);
        if (false === $numPlaceholders || $numPlaceholders < 0) {
            throw new ShouldNotHappenException();
        }

        return $numPlaceholders;
    }
}
