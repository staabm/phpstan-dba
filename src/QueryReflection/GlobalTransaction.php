<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\QueryReflection;

use mysqli;
use PDO;
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

        register_shutdown_function(function() use ($connection) {
            if (self::$inTransaction) {
                self::$inTransaction = false;

                if ($connection instanceof PDO) {
                    $connection->rollBack();
                } else {
                    $connection->rollback();
                }
            }
        });
    }
}
