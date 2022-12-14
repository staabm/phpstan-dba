<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Analyzer;

interface Analyzer
{
    public function analyze(string $query): QueryPlanResult;
}
