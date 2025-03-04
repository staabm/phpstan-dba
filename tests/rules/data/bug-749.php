<?php

namespace Bug749;

use Doctrine\DBAL\Connection;

class Test
{
    private Connection $connection;

    /**
     * @param array<int> $ids
     */
    public function getIds(array $ids): array
    {
        return $this
            ->connection
            ->executeQuery(
                'SELECT id FROM table WHERE err-or id IN ('. self::inPlaceholders($ids) .')',
                [...$ids],
            )
            ->fetchFirstColumn();
    }

    /**
     * Returns a string containing all required "?"-placeholders to pass $ids into a IN()-expression.
     *
     * @phpstandba-inference-placeholder '?'
     *
     * @param non-empty-array<int|string> $ids
     *
     * @return literal-string
     */
    public static function inPlaceholders(array $ids): string
    {
        // no matter whether $ids contains user input or not,
        // we can safely say what we return here will no longer contain user input.
        // therefore we type the return with "literal-string", so we don't get errors like
        // "ClxProductNet_DbStatement constructor expects literal-string, non-falsy-string given." at the caller site
        return implode(',', array_fill(0, count($ids), '?'));
    }
}
