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

        $queryReflection = new QueryReflection();
        $queryString = $queryReflection->resolveQueryString($queryExpr, $scope);
        if (null === $queryString) {
            return [];
        }

        $args = $methodCall->getArgs();
        $placeholderCount = $queryReflection->countPlaceholders($queryString);

        if (0 === \count($args)) {
            if (0 === $placeholderCount) {
                return [];
            }

            $placeholderExpectation = sprintf('Query expects %s placeholder', $placeholderCount);
            if ($placeholderCount > 1) {
                $placeholderExpectation = sprintf('Query expects %s placeholders', $placeholderCount);
            }

            return [
                RuleErrorBuilder::message(sprintf($placeholderExpectation.', but no values are given to execute().', $placeholderCount))->line($methodCall->getLine())->build(),
            ];
        }

        return $this->checkParameterValues($methodCall, $scope, $queryString, $placeholderCount);
    }

    /**
     * @return RuleError[]
     */
    private function checkParameterValues(MethodCall $methodCall, Scope $scope, string $queryString, int $placeholderCount): array
    {
        $queryReflection = new QueryReflection();
        $args = $methodCall->getArgs();

        $parameterTypes = $scope->getType($args[0]->value);
        $parameters = $queryReflection->resolveParameters($parameterTypes);
        if (null === $parameters) {
            return [];
        }
        $parameterCount = \count($parameters);

        if ($parameterCount !== $placeholderCount) {
            $placeholderExpectation = sprintf('Query expects %s placeholder', $placeholderCount);
            if ($placeholderCount > 1) {
                $placeholderExpectation = sprintf('Query expects %s placeholders', $placeholderCount);
            }

            $parameterActual = sprintf('but %s value is given to execute()', $parameterCount);
            if ($parameterCount > 1) {
                $parameterActual = sprintf('but %s values are given to execute()', $parameterCount);
            }

            return [
                RuleErrorBuilder::message($placeholderExpectation.', '.$parameterActual.'.')->line($methodCall->getLine())->build(),
            ];
        }

        $errors = [];
        $namedPlaceholders = $queryReflection->extractNamedPlaceholders($queryString);
        if (\count($namedPlaceholders) > 0) {
            foreach ($namedPlaceholders as $namedPlaceholder) {
                if (!\array_key_exists($namedPlaceholder, $parameters)) {
                    $errors[] = RuleErrorBuilder::message(sprintf('Query expects placeholder %s, but it is missing from values given to execute().', $namedPlaceholder))->line($methodCall->getLine())->build();
                }
            }

            foreach ($parameters as $placeholderKey => $value) {
                if (!\in_array($placeholderKey, $namedPlaceholders)) {
                    $errors[] = RuleErrorBuilder::message(sprintf('Value %s is given to execute(), but the query does not contain this placeholder.', $placeholderKey))->line($methodCall->getLine())->build();
                }
            }
        }

        return $errors;
    }
}
