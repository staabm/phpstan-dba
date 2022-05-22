<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Analyzer;

final class QueryPlanResult {
    const NO_INDDEX = "no-index";
    const NOT_EFFIECIENT = "inefficient";

    /**
     * @var array<string, self::*>
     */
    private $result = [];

    /**
     * @param string $table
     * @param self::* $result
     * @return void
     */
    public function addRow(string $table, string $result) {
        $this->result[$table] = $result;
    }

    /**
     * @return string[]
     */
    public function getTablesNotUsingIndex():array {
        $tables = [];
        foreach($this->result as $table => $result) {
            if ($result === self::NO_INDDEX) {
                $tables[] = $table;
            }
        }
        return $tables;
    }

    /**
     * @return string[]
     */
    public function getTablesNotEfficient():array {
        $tables = [];
        foreach($this->result as $table => $result) {
            if ($result === self::NOT_EFFIECIENT) {
                $tables[] = $table;
            }
        }
        return $tables;
    }
}
