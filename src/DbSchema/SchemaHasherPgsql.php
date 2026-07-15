<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\DbSchema;

use PDO;
use PHPStan\ShouldNotHappenException;
use staabm\PHPStanDba\DbaException;
use staabm\PHPStanDba\QueryReflection\GlobalTransaction;

final class SchemaHasherPgsql implements SchemaHasher
{
    private PDO $connection;

    private ?string $hash = null;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    public function hashDb(): string
    {
        if (null !== $this->hash) {
            return $this->hash;
        }

        GlobalTransaction::ensureStarted($this->connection);

        $query = <<<'SQL'
            SELECT MD5(
                COALESCE(
                    STRING_AGG(
                        CONCAT_WS(
                            CHR(31),
                            namespace.nspname,
                            relation.relname,
                            attribute.attname,
                            FORMAT_TYPE(attribute.atttypid, attribute.atttypmod),
                            attribute.attnotnull::text
                        ),
                        CHR(30)
                        ORDER BY namespace.nspname, relation.relname, attribute.attnum
                    ),
                    ''
                )
            ) AS dbsignature
            FROM pg_catalog.pg_attribute attribute
            INNER JOIN pg_catalog.pg_class relation ON relation.oid = attribute.attrelid
            INNER JOIN pg_catalog.pg_namespace namespace ON namespace.oid = relation.relnamespace
            WHERE relation.relkind IN ('r', 'p', 'v', 'm', 'f')
                AND attribute.attnum > 0
                AND NOT attribute.attisdropped
                AND namespace.nspname NOT IN ('pg_catalog', 'information_schema')
                AND namespace.nspname !~ '^pg_(toast|temp)'
            SQL;

        try {
            $stmt = $this->connection->query($query);
            $hash = $stmt->fetchColumn();
        } catch (\Throwable $e) {
            throw new DbaException('Failed to hash PostgreSQL schema', 0, $e);
        }

        if (! \is_string($hash) || '' === $hash) {
            throw new ShouldNotHappenException();
        }

        return $this->hash = $hash;
    }
}
