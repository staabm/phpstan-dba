<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Tests\Fixture;

class StaticDatabase
{
    public static function query(string $sql, int $fetchMode = null): mixed
    {
        return null;
    }

    public static function prepare(string $sql): mixed
    {
        return null;
    }

    public static function preparedQuery(string $sql, array $params = []): mixed
    {
        return null;
    }

    public static function executeQuery(string $sql, array $params = []): mixed
    {
        return null;
    }
}
