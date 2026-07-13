<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\QueryReflection;

use mysqli;
use PDO;
use PDOException;
use Throwable;
use function register_shutdown_function;

final class GlobalTransaction {
    static private bool $inTransaction = false;

    /**
     * @param mysqli|PDO $connection
     */
    static public function ensureStarted($connection):void {
        if (self::$inTransaction) {
            return;
        }
        self::$inTransaction = true;

        try {
            if (QueryReflection::getRuntimeConfiguration()->isAnalyzingWriteQueries()) {
                if ($connection instanceof PDO) {
                    $connection->beginTransaction();
                } else {
                    $connection->begin_transaction();
                }
            } else {
                if ($connection instanceof PDO) {
                    $connection->beginTransaction();
                } else {
                    $connection->begin_transaction(\MYSQLI_TRANS_START_READ_ONLY);
                }
            }
        } catch (Throwable $e) {
            // not all drivers may support transactions
            throw new \RuntimeException('Failed to start transaction', $e->getCode(), $e);
        }

        register_shutdown_function(function () use ($connection): void {
            if (! self::$inTransaction) {
                return;
            }

            self::$inTransaction = false;

            try {
                if ($connection instanceof PDO) {
                    if ($connection->inTransaction()) {
                        $connection->rollBack();
                    }
                } else {
                    $connection->rollback();
                }
            } catch (Throwable $e) {
                // ignore rollback failures during shutdown
            }
        });
    }
}
