<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Analyzer;

final class QueryPlanResult
{
    public const NO_INDEX = 'no-index';
    public const NOT_EFFICIENT = 'inefficient';

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
    public function getTablesNotEfficient(): array
    {
        $tables = [];
        foreach ($this->result as $table => $result) {
            if (self::NOT_EFFICIENT === $result) {
                $tables[] = $table;
            }
        }

        return $tables;
    }
}
