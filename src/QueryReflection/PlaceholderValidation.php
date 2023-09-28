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

        $queryStrings = [];
        $namedPlaceholders = false;
        foreach ($queryReflection->resolveQueryStrings($queryExpr, $scope) as $queryString) {
            $queryStrings[] = $queryString;

            if ($queryReflection->containsNamedPlaceholders($queryString, $parameters)) {
                $namedPlaceholders = true;
            }
        }

        if ($namedPlaceholders) {
            yield from $this->validateNamedPlaceholders($queryStrings, $parameters);

            return;
        }

        foreach($queryStrings as $queryString) {
            $placeholderCount = $queryReflection->countPlaceholders($queryString);
            yield from $this->validateUnnamedPlaceholders($parameters, $placeholderCount);
        }
    }

    /**
     * @param array<string|int, Parameter> $parameters
     *
     * @return iterable<string>
     */
    private function validateUnnamedPlaceholders(array $parameters, int $placeholderCount): iterable
    {
        $parameterCount = \count($parameters);
        $minParameterCount = 0;
        foreach ($parameters as $parameter) {
            if ($parameter->isOptional) {
                continue;
            }
            ++$minParameterCount;
        }

        if (0 === $parameterCount && 0 === $minParameterCount && 0 === $placeholderCount) {
            return;
        }

        if ($parameterCount !== $placeholderCount && $placeholderCount !== $minParameterCount) {
            $placeholderExpectation = sprintf('Query expects %s placeholder', $placeholderCount);
            if ($placeholderCount > 1) {
                $placeholderExpectation = sprintf('Query expects %s placeholders', $placeholderCount);
            }

            if (0 === $parameterCount) {
                $parameterActual = 'but no values are given';
            } elseif ($minParameterCount !== $parameterCount) {
                $parameterActual = sprintf('but %s values are given', $minParameterCount . '-' . $parameterCount);
            } else {
                $parameterActual = sprintf('but %s value is given', $parameterCount);
                if ($parameterCount > 1) {
                    $parameterActual = sprintf('but %s values are given', $parameterCount);
                }
            }

            yield $placeholderExpectation . ', ' . $parameterActual . '.';
        }
    }

    /**
     * @param list<string> $queryStrings
     * @param array<string|int, Parameter> $parameters
     *
     * @return iterable<string>
     */
    private function validateNamedPlaceholders(array $queryStrings, array $parameters): iterable
    {
        $queryReflection = new QueryReflection();

        $allNamedPlaceholders = [];
        foreach($queryStrings as $queryString) {
            $namedPlaceholders = $queryReflection->extractNamedPlaceholders($queryString);

            foreach ($namedPlaceholders as $namedPlaceholder) {
                if (! \array_key_exists($namedPlaceholder, $parameters)) {
                    yield sprintf('Query expects placeholder %s, but it is missing from values given.', $namedPlaceholder);
                }

                $allNamedPlaceholders[] = $namedPlaceholder;
            }
        }

        foreach ($parameters as $placeholderKey => $parameter) {
            if (\is_int($placeholderKey)) {
                continue;
            }
            if ($parameter->isOptional) {
                continue;
            }
            if (! \in_array($placeholderKey, $allNamedPlaceholders, true)) {
                yield sprintf('Value %s is given, but the query does not contain this placeholder.', $placeholderKey);
            }
        }
    }
}
