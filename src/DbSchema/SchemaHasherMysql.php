<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\DbSchema;

use mysqli;
use PDO;
use PHPStan\ShouldNotHappenException;

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

        // for a schema with 3.000 columns we need roughly
        // 70.000 group concat max length
        $maxConcatQuery = 'SET SESSION group_concat_max_len = 1000000';
        $this->connection->query($maxConcatQuery);

        $query = "
            SELECT
                MD5(
                    GROUP_CONCAT(
                        InnerSelect.columns
                    )
                ) AS dbsignature,
                1 AS grouper
            FROM (
                SELECT
                    CONCAT(
                        COALESCE(COLUMN_NAME, ''),
                        COALESCE(EXTRA, ''),
                        COLUMN_TYPE,
                        IS_NULLABLE
                    ) as columns
                FROM
                    information_schema.columns
                WHERE
                    table_schema = DATABASE()
                ORDER BY table_name, column_name
            ) as InnerSelect
            GROUP BY
                grouper";

        $hash = '';
        if ($this->connection instanceof PDO) {
            $this->connection->beginTransaction();

            try {
                $stmt = $this->connection->query($query);
                foreach ($stmt as $row) {
                    $hash = $row['dbsignature'] ?? '';
                }
            } finally {
                $this->connection->rollBack();
            }
        } else {
            $this->connection->begin_transaction(\MYSQLI_TRANS_START_READ_ONLY);

            try {
                $result = $this->connection->query($query);
                if ($result instanceof \mysqli_result) { // @phpstan-ignore instanceof.alwaysTrue
                    $row = $result->fetch_assoc();
                    $hash = $row['dbsignature'] ?? '';
                }
            } finally {
                $this->connection->rollback();
            }
        }

        if ('' === $hash) {
            throw new ShouldNotHappenException();
        }

        return $this->hash = $hash;
    }
}
