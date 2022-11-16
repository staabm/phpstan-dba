<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\DibiReflection;

final class DibiReflection
{
    public function rewriteQuery(string $queryString): ?string
    {
        $queryString = str_replace('%in', '(1)', $queryString);
        $queryString = str_replace('%lmt', 'LIMIT 1', $queryString);
        $queryString = str_replace('%ofs', ', 1', $queryString);
        $queryString = preg_replace('#%(i|s)#', '"1"', $queryString) ?? '';
        $queryString = preg_replace('#%(t|d)#', '"2000-1-1"', $queryString) ?? '';
        $queryString = preg_replace('#%(and|or)#', '(1 = 1)', $queryString) ?? '';
        $queryString = preg_replace('#%~?like~?#', '"%1%"', $queryString) ?? '';

        if (strpos($queryString, '%n') > 0) {
            $queryString = null;
        } elseif (strpos($queryString, '%ex') > 0) {
            $queryString = null;
        } elseif (0 !== preg_match('#^\s*(START|ROLLBACK|SET|SAVEPOINT|SHOW)#i', $queryString)) {
            $queryString = null;
        }

        return $queryString;
    }
}
