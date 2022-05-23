<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Analyzer;

use PDO;
use mysqli;
use PHPStan\ShouldNotHappenException;

final class QueryPlanAnalyzerMysql {
    /**
     * @var PDO|mysqli
     */
    private $connection;

    /**
     * @param PDO|mysqli $connection
     */
    public function __construct($connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param non-empty-string $query
     * @return QueryPlanResult
     */
    public function analyze(string $query):QueryPlanResult {

        if ($this->connection instanceof PDO) {
            $stmt = $this->connection->query('EXPLAIN '. $query);
            // @phpstan-ignore-next-line we cannot type the iterator
            return $this->buildResult($stmt);

        } else {
            $result = $this->connection->query('EXPLAIN '. $query);
            if ($result instanceof \mysqli_result) {
                // @phpstan-ignore-next-line we cannot type the iterator
                return $this->buildResult($result);
            }
        }

        throw new ShouldNotHappenException();
    }

    /**
     * @param \Iterator<array-key, array{key: string|null, rows: positive-int, table: string}> $it
     * @return QueryPlanResult
     */
    private function buildResult(\Iterator $it):QueryPlanResult {
        $result = new QueryPlanResult();

        foreach ($it as $row) {
            if ($row['key'] === null) {
                $result->addRow($row['table'], QueryPlanResult::NO_INDDEX);
            }elseif ($row['rows'] > 100000) {
                $result->addRow($row['table'], QueryPlanResult::NOT_EFFIECIENT);
            }
        }

        return $result;
    }
}
