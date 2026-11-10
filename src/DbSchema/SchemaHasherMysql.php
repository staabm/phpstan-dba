<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\DbSchema;

use mysqli;
use PDO;
use PHPStan\ShouldNotHappenException;
use staabm\PHPStanDba\DbaException;
use staabm\PHPStanDba\QueryReflection\GlobalTransaction;

final class SchemaHasherMysql implements SchemaHasher
{
    /**
     * @var PDO|mysqli
     */
    private $connection;

    private ?string $hash = null;

    /**
     * @param PDO|mysqli $connection
     */
    public function __construct($connection)
    {
        $this->connection = $connection;
    }

    public function hashDb(): string
    {
        if (null !== $this->hash) {
            return $this->hash;
        }

        GlobalTransaction::ensureStarted($this->connection);

        // for a schema with 3.000 columns we need roughly
        // 70.000 group concat max length
        $maxConcatQuery = 'SET SESSION group_concat_max_len = 1000000';
        try {
            $this->connection->query($maxConcatQuery);
        } catch (\Throwable $e) {
            throw new DbaException('Failed to set group_concat_max_len', 0, $e);
        }

        $query = "
            SELECT
                MD5(
                    GROUP_CONCAT(
                        CONCAT(
                            COALESCE(COLUMN_NAME, ''),
                            COALESCE(EXTRA, ''),
                            COLUMN_TYPE,
                            IS_NULLABLE,
                            ORDINAL_POSITION
                        )
                        ORDER BY TABLE_NAME, COLUMN_NAME
                    )
                ) AS dbsignature
            FROM
                information_schema.columns
            WHERE
                table_schema = DATABASE()";

        $hash = '';
        if ($this->connection instanceof PDO) {
            $stmt = $this->connection->query($query);
            foreach ($stmt as $row) {
                $hash = $row['dbsignature'] ?? '';
            }
        } else {
            $result = $this->connection->query($query);
            if ($result instanceof \mysqli_result) { // @phpstan-ignore instanceof.alwaysTrue
                $row = $result->fetch_assoc();
                $hash = $row['dbsignature'] ?? '';
            }
        }

        if ('' === $hash) {
            throw new ShouldNotHappenException();
        }

        return $this->hash = $hash;
    }
}
