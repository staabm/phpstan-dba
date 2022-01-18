<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\QueryReflection;

final class PlaceholderValidation
{
    /**
     * @param array<string|int, scalar|null> $parameters
     *
     * @return iterable<string>
     */
    public function checkErrors(string $queryString, array $parameters): iterable
    {
        $queryReflection = new QueryReflection();
        $placeholderCount = $queryReflection->countPlaceholders($queryString);

        if (0 === \count($parameters)) {
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

        yield from $this->checkParameterValues($queryString, $parameters, $placeholderCount);
    }

    /**
     * @param array<string|int, scalar|null> $parameters
     *
     * @return iterable<string>
     */
    private function checkParameterValues(string $queryString, array $parameters, int $placeholderCount): iterable
    {
        $queryReflection = new QueryReflection();

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
