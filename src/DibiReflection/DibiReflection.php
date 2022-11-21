<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\DibiReflection;

final class DibiReflection
{
    public function rewriteQuery(string $queryString): ?string
    {
        // see https://dibiphp.com/en/documentation#toc-modifiers
        $queryString = str_replace('%lmt', 'LIMIT 1', $queryString);
        $queryString = str_replace('%ofs', ', 1', $queryString);
        $queryString = str_replace('%in', '(1)', $queryString);
        $queryString = str_replace('%l', '(1)', $queryString);
        $queryString = preg_replace('#%(bin|sN|iN|f|i|s)#', "'1'", $queryString) ?? '';
        $queryString = preg_replace('#%(t|d|dt)#', '"2000-1-1"', $queryString) ?? '';
        $queryString = preg_replace('#%(and|or)#', '(1 = 1)', $queryString) ?? '';
        $queryString = preg_replace('#%~?like~?#', '"%1%"', $queryString) ?? '';

        $unsupportedPlaceholders = ['%ex', '%n', '%by', '%sql', '%m', '%N'];

        foreach ($unsupportedPlaceholders as $unsupportedPlaceholder) {
            if (strpos($queryString, $unsupportedPlaceholder) > 0) {
                return null;
            }
        }

        if (0 !== preg_match('#^\s*(START|ROLLBACK|SET|SAVEPOINT|SHOW)#i', $queryString)) {
            $queryString = null;
        }

        return $queryString;
    }
}
