<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Tests;

use PDO;
use PHPUnit\Framework\TestCase;
use staabm\PHPStanDba\QueryReflection\QueryReflection;

final class SchemaHasherPgsqlTest extends TestCase
{
    private const TABLE_NAME = 'phpstan_dba_schema_hash_test';

    public function testSchemaHashChangesWithTableColumns(): void
    {
        if ('pdo-pgsql' !== getenv('DBA_REFLECTOR')) {
            self::markTestSkipped('PostgreSQL reflector required.');
        }

        $pdo = self::createPdo();
        $pdo->exec('DROP TABLE IF EXISTS ' . self::TABLE_NAME);
        $pdo->exec('CREATE TABLE ' . self::TABLE_NAME . ' (id integer NOT NULL)');

        try {
            $initialHash = self::hashDb();
            self::assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $initialHash);
            self::assertSame($initialHash, self::hashDb());

            $pdo->exec('ALTER TABLE ' . self::TABLE_NAME . ' RENAME COLUMN id TO record_id');
            $renamedColumnHash = self::hashDb();
            self::assertNotSame($initialHash, $renamedColumnHash);

            $pdo->exec('ALTER TABLE ' . self::TABLE_NAME . ' ALTER COLUMN record_id TYPE bigint');
            $changedTypeHash = self::hashDb();
            self::assertNotSame($renamedColumnHash, $changedTypeHash);

            $pdo->exec('ALTER TABLE ' . self::TABLE_NAME . ' ALTER COLUMN record_id DROP NOT NULL');
            self::assertNotSame($changedTypeHash, self::hashDb());
        } finally {
            $pdo->exec('DROP TABLE IF EXISTS ' . self::TABLE_NAME);
        }
    }

    private static function hashDb(): string
    {
        return (new QueryReflection())->getSchemaHasher()->hashDb();
    }

    private static function createPdo(): PDO
    {
        $host = self::env('DBA_HOST', '127.0.0.1');
        $user = self::env('DBA_USER');
        $password = self::env('DBA_PASSWORD');
        $database = self::env('DBA_DATABASE');

        $port = '';
        if (str_contains($host, ':')) {
            [$host, $port] = explode(':', $host, 2);
            $port = ';port=' . $port;
        }

        return new PDO(
            sprintf('pgsql:dbname=%s;host=%s', $database, $host) . $port,
            $user,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]
        );
    }

    private static function env(string $name, ?string $default = null): string
    {
        $value = getenv($name);
        if (false === $value) {
            $value = $_ENV[$name] ?? $default;
        }
        if (! \is_string($value)) {
            throw new \RuntimeException(sprintf('Missing environment variable %s.', $name));
        }

        return $value;
    }
}
