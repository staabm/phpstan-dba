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
     * @param array<int> $ids
     */
    public function getMoreIds(string $s, array $ids): array
    {
        return $this
            ->connection
            ->executeQuery(
                'SELECT id FROM table WHERE a=? AND err-or id IN ('. self::inPlaceholders($ids) .')',
                [
                    $s,
                    ...$ids
                ],
            )
            ->fetchFirstColumn();
    }

    /**
     * @param array<int> $ids
     */
    public function getArrayIds(array $ids): array
    {
        return $this
            ->connection
            ->executeQuery(
                'SELECT akid FROM ak WHERE akid IN ('. self::inPlaceholders($ids) .')',
                $ids,
            )
            ->fetchFirstColumn();
    }

    /**
     * @param array<int> $ids
     */
    public function getArrayIdMix(int $i, array $ids): array
    {
        return $this
            ->connection
            ->executeQuery(
                'SELECT akid FROM ak WHERE eladaid = ? AND akid IN ('. self::inPlaceholders($ids) .')',
                array_merge([$i], $ids),
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
        return implode(',', array_fill(0, count($ids), '?'));
    }
}
