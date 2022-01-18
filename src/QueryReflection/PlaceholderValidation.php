<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\QueryReflection;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;

final class PlaceholderValidation
{
    /**
     * @return iterable<string>
     */
    public function checkErrors(string $queryString, MethodCall $methodCall, Scope $scope): iterable
    {
        $queryReflection = new QueryReflection();
        $args = $methodCall->getArgs();
        $placeholderCount = $queryReflection->countPlaceholders($queryString);

        if (0 === \count($args)) {
            if (0 === $placeholderCount) {
                return;
            }

            $placeholderExpectation = sprintf('Query expects %s placeholder', $placeholderCount);
            if ($placeholderCount > 1) {
                $placeholderExpectation = sprintf('Query expects %s placeholders', $placeholderCount);
            }

            yield sprintf($placeholderExpectation.', but no values are given to execute().', $placeholderCount);

            return;
        }

        yield from $this->checkParameterValues($methodCall, $scope, $queryString, $placeholderCount);
    }

    /**
     * @return iterable<string>
     */
    private function checkParameterValues(MethodCall $methodCall, Scope $scope, string $queryString, int $placeholderCount): iterable
    {
        $queryReflection = new QueryReflection();
        $args = $methodCall->getArgs();

        $parameterTypes = $scope->getType($args[0]->value);
        $parameters = $queryReflection->resolveParameters($parameterTypes);
        if (null === $parameters) {
            return;
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

            yield $placeholderExpectation.', '.$parameterActual.'.';

            return;
        }

        $namedPlaceholders = $queryReflection->extractNamedPlaceholders($queryString);
        if (\count($namedPlaceholders) > 0) {
            foreach ($namedPlaceholders as $namedPlaceholder) {
                if (!\array_key_exists($namedPlaceholder, $parameters)) {
                    yield sprintf('Query expects placeholder %s, but it is missing from values given to execute().', $namedPlaceholder);
                }
            }

            foreach ($parameters as $placeholderKey => $value) {
                if (!\in_array($placeholderKey, $namedPlaceholders)) {
                    yield sprintf('Value %s is given to execute(), but the query does not contain this placeholder.', $placeholderKey);
                }
            }
        }
    }
}
