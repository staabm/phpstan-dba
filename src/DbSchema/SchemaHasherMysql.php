<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\DbSchema;

use mysqli;
use PDO;

final class SchemaHasherMysql {
    /**
     * @var PDO|mysqli $connection
     */
    private $connection;

    /**
     * @param PDO|mysqli $connection
     */
    public function __construct($connection) {
        $this->connection = $connection;
    }

    public function hash(): string {
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

        if ($this->connection instanceof PDO) {
            $result = $this->connection->query($query);

            return isset($result[0]) ? $result[0]['dbsignature'] : '';
        } else {
            $result = $this->connection->query($query);
            $row = $result->fetch_assoc();

            return $row['dbsignature'] ?? '';
        }
    }
}
