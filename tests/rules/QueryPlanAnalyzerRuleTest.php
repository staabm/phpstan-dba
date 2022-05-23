<?php

namespace staabm\PHPStanDba\Tests;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use staabm\PHPStanDba\Rules\QueryPlanAnalyzerRule;

/**
 * @extends RuleTestCase<QueryPlanAnalyzerRule>
 */
class QueryPlanAnalyzerRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(QueryPlanAnalyzerRule::class);
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__.'/config/query-plan-analyzer.neon',
        ];
    }

    public function testParameterErrors(): void
    {
        $this->analyse([__DIR__.'/data/query-plan-analyzer.php'], [
            [
                'Query plan analyzer: table "ada" is not using an index',
                9,
            ],
        ]);
    }
}
