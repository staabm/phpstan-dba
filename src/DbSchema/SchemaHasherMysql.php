<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\DbSchema;

use mysqli;
use PDO;
use PHPStan\ShouldNotHappenException;

final class SchemaHasherMysql
{
    /**
     * @var PDO|mysqli
     */
    private $connection;

    /**
     * @var string|null
     */
    private $hash = null;

    /**
     * @param PDO|mysqli $connection
     */
    public function __construct($connection)
    {
        if ($connection instanceof PDO) {
            $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } else {
            // enable exception throwing on php <8.1
            mysqli_report(\MYSQLI_REPORT_ERROR | \MYSQLI_REPORT_STRICT);
        }
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

        $query = '
            SELECT
                MD5(
                    GROUP_CONCAT(
                        CONCAT(
                            COALESCE(COLUMN_NAME, ""),
                            COALESCE(EXTRA, ""),
                            COLUMN_TYPE,
                            IS_NULLABLE
                        )
                    )
                ) AS dbsignature,
                1 AS grouper
            FROM
                information_schema.columns
            WHERE
                table_schema = DATABASE()
            GROUP BY
                grouper';

        $hash = '';
        if ($this->connection instanceof PDO) {
            $this->connection->beginTransaction();

            try {
                $stmt = $this->connection->query($query);
                var_dump($stmt);
                foreach ($stmt as $row) {
                    var_dump($row);
                    $hash = $row['dbsignature'] ?? '';
                }
                var_dump($hash);
            } finally {
                $this->connection->rollBack();
            }
        } else {
            $this->connection->begin_transaction(\MYSQLI_TRANS_START_READ_ONLY);
            var_dump($this->connection->error);

            try {
                $result = $this->connection->query($query);
                var_dump($this->connection->error);
                if ($result instanceof \mysqli_result) {
                    $row = $result->fetch_assoc();
                    $hash = $row['dbsignature'] ?? '';
                    var_dump($row);
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
