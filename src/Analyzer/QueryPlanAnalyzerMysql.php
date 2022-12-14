<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Analyzer;

use mysqli;
use PDO;
use PHPStan\ShouldNotHappenException;
use staabm\PHPStanDba\QueryReflection\QueryReflection;

final class QueryPlanAnalyzerMysql implements Analyzer
{
    /**
     * @deprecated use QueryPlanAnalyzer::DEFAULT_UNINDEXED_READS_THRESHOLD instead
     */
    public const DEFAULT_UNINDEXED_READS_THRESHOLD = QueryPlanAnalyzer::DEFAULT_UNINDEXED_READS_THRESHOLD;
    /**
     * @deprecated use QueryPlanAnalyzer::TABLES_WITHOUT_DATA instead
     */
    public const TABLES_WITHOUT_DATA = QueryPlanAnalyzer::TABLES_WITHOUT_DATA;
    /**
     * @deprecated use QueryPlanAnalyzer::DEFAULT_SMALL_TABLE_THRESHOLD instead
     */
    public const DEFAULT_SMALL_TABLE_THRESHOLD = QueryPlanAnalyzer::DEFAULT_SMALL_TABLE_THRESHOLD;

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
        $simulatedQuery = 'EXPLAIN '.$query;

        if ($this->connection instanceof PDO) {
            $stmt = $this->connection->query($simulatedQuery);

            // @phpstan-ignore-next-line
            return $this->buildResult($simulatedQuery, $stmt);
        } else {
            $result = $this->connection->query($simulatedQuery);
            if ($result instanceof \mysqli_result) {
                return $this->buildResult($simulatedQuery, $result);
            }
        }

        throw new ShouldNotHappenException();
    }

    /**
     * @param \IteratorAggregate<array-key, array{select_type: string, key: string|null, type: string|null, rows: positive-int, table: ?string}> $it
     */
    private function buildResult(string $simulatedQuery, $it): QueryPlanResult
    {
        $result = new QueryPlanResult($simulatedQuery);

        $allowedUnindexedReads = QueryReflection::getRuntimeConfiguration()->getNumberOfAllowedUnindexedReads();
        if (false === $allowedUnindexedReads) {
            throw new ShouldNotHappenException();
        }

        $allowedRowsNotRequiringIndex = QueryReflection::getRuntimeConfiguration()->getNumberOfRowsNotRequiringIndex();
        if (false === $allowedRowsNotRequiringIndex) {
            throw new ShouldNotHappenException();
        }

        foreach ($it as $row) {
            // mysql might return 'no matching row in const table'
            if (null === $row['table']) {
                continue;
            }

            if (null === $row['key'] && $row['rows'] >= $allowedRowsNotRequiringIndex) {
                // derived table aka. a expression that generates a table within the scope of a query FROM clause
                // is a temporary table, which indexes cannot be created for.
                if ('derived' === strtolower($row['select_type'])) {
                    continue;
                }

                $result->addRow($row['table'], QueryPlanResult::ROW_NO_INDEX);
            } else {
                // don't analyse maybe existing data, to make the result consistent with empty db schemas
                if (QueryPlanAnalyzer::TABLES_WITHOUT_DATA === $allowedRowsNotRequiringIndex) {
                    continue;
                }

                if (null !== $row['type'] && 'all' === strtolower($row['type']) && $row['rows'] >= $allowedRowsNotRequiringIndex) {
                    $result->addRow($row['table'], QueryPlanResult::ROW_TABLE_SCAN);
                } elseif (true === $allowedUnindexedReads && $row['rows'] >= QueryPlanAnalyzer::DEFAULT_UNINDEXED_READS_THRESHOLD) {
                    $result->addRow($row['table'], QueryPlanResult::ROW_UNINDEXED_READS);
                } elseif (\is_int($allowedUnindexedReads) && $row['rows'] >= $allowedUnindexedReads) {
                    $result->addRow($row['table'], QueryPlanResult::ROW_UNINDEXED_READS);
                }
            }
        }

        return $result;
    }
}
