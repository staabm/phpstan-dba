<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\QueryReflection;

use PhpParser\Node\Expr;
use PHPStan\Analyser\Scope;

final class PlaceholderValidation
{
    /**
     * @param array<string|int, Parameter> $parameters
     *
     * @return iterable<string>
     */
    public function checkQuery(Expr $queryExpr, Scope $scope, array $parameters): iterable
    {
        $queryReflection = new QueryReflection();

        foreach ($queryReflection->resolveQueryStrings($queryExpr, $scope) as $queryString) {
            foreach ($this->checkErrors($queryString, $parameters) as $error) {
                yield $error;
            }
        }
    }

    /**
     * @param array<string|int, Parameter> $parameters
     *
     * @return iterable<string>
     */
    private function checkErrors(string $queryString, array $parameters): iterable
    {
        if ('SELECT' !== QueryReflection::getQueryType($queryString)) {
            return;
        }

        $queryReflection = new QueryReflection();
        $placeholderCount = $queryReflection->countPlaceholders($queryString);

        $parameterCount = \count($parameters);
        $minParameterCount = 0;
        foreach ($parameters as $parameter) {
            if ($parameter->isOptional) {
                continue;
            }
            ++$minParameterCount;
        }

        if (0 === $parameterCount && 0 === $minParameterCount) {
            if (0 === $placeholderCount) {
                return;
            }

            $placeholderExpectation = sprintf('Query expects %s placeholder', $placeholderCount);
            if ($placeholderCount > 1) {
                $placeholderExpectation = sprintf('Query expects %s placeholders', $placeholderCount);
            }

            yield sprintf($placeholderExpectation.', but no values are given.', $placeholderCount);

            return;
        }

        yield from $this->checkParameterValues($queryString, $parameters, $placeholderCount);
    }

    /**
     * @param non-empty-array<string|int, Parameter> $parameters
     *
     * @return iterable<string>
     */
    private function checkParameterValues(string $queryString, array $parameters, int $placeholderCount): iterable
    {
        $queryReflection = new QueryReflection();

        $parameterCount = \count($parameters);
        $minParameterCount = 0;
        foreach ($parameters as $parameter) {
            if ($parameter->isOptional) {
                continue;
            }
            ++$minParameterCount;
        }

        if ($parameterCount !== $placeholderCount && $placeholderCount !== $minParameterCount) {
            $placeholderExpectation = sprintf('Query expects %s placeholder', $placeholderCount);
            if ($placeholderCount > 1) {
                $placeholderExpectation = sprintf('Query expects %s placeholders', $placeholderCount);
            }

            if ($minParameterCount !== $parameterCount) {
                $parameterActual = sprintf('but %s values are given', $minParameterCount.'-'.$parameterCount);
            } else {
                $parameterActual = sprintf('but %s value is given', $parameterCount);
                if ($parameterCount > 1) {
                    $parameterActual = sprintf('but %s values are given', $parameterCount);
                }
            }

            yield $placeholderExpectation.', '.$parameterActual.'.';

            return;
        }

        $namedPlaceholders = $queryReflection->extractNamedPlaceholders($queryString);
        foreach ($namedPlaceholders as $namedPlaceholder) {
            if (!\array_key_exists($namedPlaceholder, $parameters)) {
                yield sprintf('Query expects placeholder %s, but it is missing from values given.', $namedPlaceholder);
            }
        }

        foreach ($parameters as $placeholderKey => $parameter) {
            if (\is_int($placeholderKey)) {
                continue;
            }
            if ($parameter->isOptional) {
                continue;
            }
            if (!\in_array($placeholderKey, $namedPlaceholders)) {
                yield sprintf('Value %s is given, but the query does not contain this placeholder.', $placeholderKey);
            }
        }
    }
}
