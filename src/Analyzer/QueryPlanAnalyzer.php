<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Analyzer;

final class QueryPlanAnalyzer
{
    /**
     * number of unindexed reads allowed before a query is considered inefficient.
     */
    public const DEFAULT_UNINDEXED_READS_THRESHOLD = 100000;
    /**
     * allows analyzing queries even on empty database schemas.
     */
    public const TABLES_WITHOUT_DATA = 0;
    /**
     * max number of rows in a table, for which we don't report errors, because using a index/table-scan wouldn't improve performance.
     * requires production quality data in the database at analysis time.
     */
    public const DEFAULT_SMALL_TABLE_THRESHOLD = 5000;
}
