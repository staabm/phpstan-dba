<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Analyzer;

final class QueryPlanResult
{
    public const NO_INDEX = 'no-index';
    public const TABLE_SCAN = 'table-scan';
    public const UNINDEXED_READS = 'unindexed-reads';

    /**
     * @var array<string, self::*>
     */
    private $result = [];

    /**
     * @param self::* $result
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
            if (self::NO_INDEX === $result) {
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
            if (self::TABLE_SCAN === $result) {
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
            if (self::UNINDEXED_READS === $result) {
                $tables[] = $table;
            }
        }

        return $tables;
    }
}
