<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Analyzer;

use PDO;
use PHPStan\ShouldNotHappenException;
use staabm\PHPStanDba\QueryReflection\QueryReflection;

final class QueryPlanAnalyzerPgSql implements Analyzer
{
    /** @var PDO */
    private $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    public function analyze(string $query): QueryPlanResult
    {
        $simulatedQuery = 'EXPLAIN (FORMAT JSON) '.$query;

        $stmt = $this->connection->query($simulatedQuery);

        return $this->buildResult($simulatedQuery, $stmt);
    }

    /** @param \PDOStatement<array<float|int|string|null>> $stmt */
    private static function buildResult(string $simulatedQuery, $stmt): QueryPlanResult
    {
        $allowedUnindexedReads = QueryReflection::getRuntimeConfiguration()->getNumberOfAllowedUnindexedReads();
        if (false === $allowedUnindexedReads) {
            throw new ShouldNotHappenException();
        }

        $allowedRowsNotRequiringIndex = QueryReflection::getRuntimeConfiguration()->getNumberOfRowsNotRequiringIndex();
        if (false === $allowedRowsNotRequiringIndex) {
            throw new ShouldNotHappenException();
        }

        $queryPlanResult = new QueryPlanResult($simulatedQuery);
        $result = $stmt->fetch();
        $queryPlans = \is_array($result) && \array_key_exists('QUERY PLAN', $result)
            ? json_decode($result['QUERY PLAN'], true)
            : [];
        \assert(\is_array($queryPlans));
        foreach ($queryPlans as $queryPlan) {
            $plan = $queryPlan['Plan'];
            $table = $plan['Alias'] ?? null;

            if (null === $table) {
                continue;
            }

            $rowReads = $plan['Plan Rows'];
            $nodeType = $plan['Node Type'];
            if ('Seq Scan' === $nodeType && $rowReads >= $allowedRowsNotRequiringIndex) {
                $queryPlanResult->addRow($table, QueryPlanResult::ROW_SEQ_SCAN);
            }
            if ('Bitmap Heap Scan' === $nodeType) {
                $queryPlanResult->addRow($table, QueryPlanResult::ROW_BITMAP_HEAP_SCAN);
            }
            if ('Bitmap Index Scan' === $nodeType) {
                $queryPlanResult->addRow($table, QueryPlanResult::ROW_BITMAP_INDEX_SCAN);
            }
            if ('Index Scan' === $nodeType) {
                $queryPlanResult->addRow($table, QueryPlanResult::ROW_INDEX_SCAN);
            }
            if ('Aggregate' === $nodeType) {
                $queryPlanResult->addRow($table, QueryPlanResult::ROW_AGGREGATE);
            }
            if ('Hash Aggregate' === $nodeType) {
                $queryPlanResult->addRow($table, QueryPlanResult::ROW_HASH_AGGREGATE);
            }
        }

        return $queryPlanResult;
    }
}
