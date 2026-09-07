<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Tests;

use PDO;
use PHPUnit\Framework\TestCase;
use staabm\PHPStanDba\DbSchema\SchemaHasherMysql;
use function getenv;

class SchemaHasherMysqlTest extends TestCase
{
    /**
     * A database of our own, so the hash covers a schema this test controls entirely.
     */
    private const DATABASE_NAME = 'phpstan_dba_schema_hash_test';

    private PDO $connection;

    protected function setUp(): void
    {
        if (! \in_array(getenv('DBA_REFLECTOR'), ['mysqli', 'pdo-mysql'], true)) {
            self::markTestSkipped('MySQL reflector required.');
        }

        if (! \in_array(getenv('DBA_MODE'), [ReflectorFactory::MODE_RECORDING, ReflectorFactory::MODE_REPLAY_AND_RECORDING], true)) {
            self::markTestSkipped('Hashing a schema requires a database connection.');
        }

        $this->connection = self::connect(null);
        $this->exec('DROP DATABASE IF EXISTS ' . self::DATABASE_NAME);
        $this->exec('CREATE DATABASE ' . self::DATABASE_NAME);
        $this->exec('USE ' . self::DATABASE_NAME);
    }

    protected function tearDown(): void
    {
        if (isset($this->connection)) {
            $this->exec('DROP DATABASE IF EXISTS ' . self::DATABASE_NAME);
        }
    }

    /**
     * Regression: the columns were sorted in a derived table and aggregated
     * outside it. Whether that ORDER BY survives is up to the optimizer - when
     * the derived table is merged the sort is dropped and GROUP_CONCAT consumes
     * the rows in data dictionary order instead, which is a property of the
     * server and not of the schema.
     */
    public function testSchemaHashIsTheColumnSignatureSortedByName(): void
    {
        $this->exec('CREATE TABLE t (zebra varchar(10) NOT NULL, apple date NULL)');

        self::assertSame(md5('appledateYES2,zebravarchar(10)NO1'), $this->hashDb());
    }

    public function testSchemaHashIsStable(): void
    {
        $this->exec('CREATE TABLE t (id int NOT NULL)');

        self::assertSame($this->hashDb(), $this->hashDb());
    }

    /**
     * @dataProvider provideSchemaChanges
     */
    public function testSchemaHashChangesWithTheSchema(string $schemaChange): void
    {
        $this->exec('CREATE TABLE t (id int NOT NULL, name varchar(10) NULL)');
        $hash = $this->hashDb();

        $this->exec($schemaChange);

        self::assertNotSame($hash, $this->hashDb());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public function provideSchemaChanges(): iterable
    {
        yield 'renamed column' => ['ALTER TABLE t CHANGE id record_id int NOT NULL'];
        yield 'changed column type' => ['ALTER TABLE t MODIFY id bigint NOT NULL'];
        yield 'column turned nullable' => ['ALTER TABLE t MODIFY id int NULL'];
        yield 'reordered columns' => ['ALTER TABLE t MODIFY name varchar(10) NULL FIRST'];
        yield 'added column' => ['ALTER TABLE t ADD extra int NULL'];
        yield 'added table' => ['CREATE TABLE t2 (id int NOT NULL)'];
    }

    private function exec(string $statement): void
    {
        $this->connection->exec($statement);
    }

    /**
     * A connection of its own per hash: DDL of another connection is invisible
     * within the transaction the hasher runs in.
     */
    private function hashDb(): string
    {
        return (new SchemaHasherMysql(self::connect(self::DATABASE_NAME)))->hashDb();
    }

    private static function connect(?string $database): PDO
    {
        $host = self::env('DBA_HOST', '127.0.0.1');
        $port = '';
        if (false !== strpos($host, ':')) {
            [$host, $port] = explode(':', $host, 2);
            $port = ';port=' . $port;
        }

        $dsn = 'mysql:host=' . $host . $port;
        if (null !== $database) {
            $dsn .= ';dbname=' . $database;
        }

        return new PDO($dsn, self::env('DBA_USER', 'root'), self::env('DBA_PASSWORD', 'root'), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    }

    private static function env(string $name, string $default): string
    {
        $value = getenv($name);
        if (false === $value) {
            $value = $_ENV[$name] ?? $default;
        }

        return \is_string($value) ? $value : $default;
    }
}
