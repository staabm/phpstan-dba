<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\DbSchema;

use mysqli;
use PDO;
use PHPStan\ShouldNotHappenException;

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

    public function hash(string $databaseName): string {
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
                table_schema = ?
            GROUP BY
                grouper';

        if ($this->connection instanceof PDO) {
            $stmt = $this->connection->prepare($query);
            $stmt->execute([$databaseName]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return isset($result[0]) ? $result[0]['dbsignature'] : '';
        } else {
            $stmt = $this->connection->prepare($query);
            if ($stmt === false) {
                throw new ShouldNotHappenException('unable to prepare query');
            }

            $stmt->bind_param('s', $databaseName);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();

            return $row['dbsignature'] ?? '';
        }
    }
}
