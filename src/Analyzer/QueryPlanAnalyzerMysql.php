<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Analyzer;

use mysqli;
use PDO;
use PHPStan\ShouldNotHappenException;

final class QueryPlanAnalyzerMysql
{
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
     */
    public function analyze(string $query): QueryPlanResult
    {
        if ($this->connection instanceof PDO) {
            $stmt = $this->connection->query('EXPLAIN '.$query);

            return $this->buildResult($stmt);
        } else {
            $result = $this->connection->query('EXPLAIN '.$query);
            if ($result instanceof \mysqli_result) {
                return $this->buildResult($result);
            }
        }

        throw new ShouldNotHappenException();
    }

    /**
     * @param \IteratorAggregate<array-key, array{key: string|null, rows: positive-int, table: ?string}> $it
     */
    private function buildResult(\IteratorAggregate $it): QueryPlanResult
    {
        $result = new QueryPlanResult();

        foreach ($it as $row) {
            // we cannot analyse tables without rows -> mysql will just return 'no matching row in const table'
            if (null === $row['table']) {
                continue;
            }

            if (null === $row['key']) {
                $result->addRow($row['table'], QueryPlanResult::NO_INDEX);
            } elseif ($row['rows'] > 100000) { // XXX add RuntimeConfiguration
                $result->addRow($row['table'], QueryPlanResult::NOT_EFFICIENT);
            }
        }

        return $result;
    }
}
