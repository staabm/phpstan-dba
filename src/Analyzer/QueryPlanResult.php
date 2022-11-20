<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Analyzer;

final class QueryPlanResult
{
    public const ROW_NO_INDEX = 'no-index';
    public const ROW_TABLE_SCAN = 'table-scan';
    public const ROW_UNINDEXED_READS = 'unindexed-reads';

    /**
     * @see https://www.postgresql.org/docs/current/sql-explain.html
     * @see https://www.postgresql.org/docs/current/using-explain.html
     */
    public const ROW_AGGREGATE = 'aggregate';
    public const ROW_BITMAP_HEAP_SCAN = 'bitmap-heap-scan';
    public const ROW_BITMAP_INDEX_SCAN = 'bitmap-index-scan';
    public const ROW_HASH_AGGREGATE = 'hash-aggregate';
    public const ROW_INDEX_SCAN = 'index-scan';
    public const ROW_SEQ_SCAN = 'seq-scan';

    private const MYSQL_TIPS = [
        self::ROW_NO_INDEX => 'see Mysql Docs https://dev.mysql.com/doc/refman/8.0/en/select-optimization.html',
        self::ROW_TABLE_SCAN => 'see Mysql Docs https://dev.mysql.com/doc/refman/8.0/en/table-scan-avoidance.html',
        self::ROW_UNINDEXED_READS => 'see Mysql Docs https://dev.mysql.com/doc/refman/8.0/en/select-optimization.html',
    ];

    public const PGSQL_TIP = 'see PostgreSQL Docs https://www.postgresql.org/docs/8.1/performance-tips.html';

    /**
     * @var array<string, self::ROW_*>
     */
    private $result = [];

    /**
     * @var string
     */
    private $simulatedQuery;

    public function __construct(string $simulatedQuery)
    {
        $this->simulatedQuery = $simulatedQuery;
    }

    public function getSimulatedQuery(): string
    {
        return $this->simulatedQuery;
    }

    /**
     * @param self::ROW_* $result
     *
     * @return void
     */
    public function addRow(string $table, string $result)
    {
        $this->result[$table] = $result;
    }

    /**
     * @return string[]
     */
    public function getTablesNotUsingIndex(): array
    {
        $tables = [];
        foreach ($this->result as $table => $result) {
            if (self::ROW_NO_INDEX === $result || self::ROW_INDEX_SCAN !== $result) {
                $tables[] = $table;
            }
        }

        return $tables;
    }

    /**
     * @return string[]
     */
    public function getTablesDoingTableScan(): array
    {
        $tables = [];
        foreach ($this->result as $table => $result) {
            if (self::ROW_TABLE_SCAN === $result || self::ROW_SEQ_SCAN === $result) {
                $tables[] = $table;
            }
        }

        return $tables;
    }

    /**
     * @return string[]
     */
    public function getTablesDoingUnindexedReads(): array
    {
        $tables = [];
        foreach ($this->result as $table => $result) {
            if (self::ROW_UNINDEXED_READS === $result || self::ROW_INDEX_SCAN !== $result) {
                $tables[] = $table;
            }
        }

        return $tables;
    }

    public function getTipForTable(string $table): string
    {
        $result = $this->result[$table];

        return \in_array($result, array_keys(self::MYSQL_TIPS), true)
            ? self::MYSQL_TIPS[$this->result[$table]]
            : self::PGSQL_TIP;
    }
}
