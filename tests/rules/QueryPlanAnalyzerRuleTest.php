<?php

namespace staabm\PHPStanDba\Tests;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\Rules\QueryPlanAnalyzerRule;

/**
 * @extends RuleTestCase<QueryPlanAnalyzerRule>
 */
class QueryPlanAnalyzerRuleTest extends RuleTestCase
{
    /**
     * @var bool|0|positive-int
     */
    private $numberOfAllowedUnindexedReads;
    /**
     * @var positive-int
     */
    private $numberOfRowsNotRequiringIndex;

    protected function tearDown(): void
    {
        QueryReflection::getRuntimeConfiguration()->analyzeQueryPlans(false);
    }

    protected function getRule(): Rule
    {
        QueryReflection::getRuntimeConfiguration()->analyzeQueryPlans($this->numberOfAllowedUnindexedReads, $this->numberOfRowsNotRequiringIndex);

        return self::getContainer()->getByType(QueryPlanAnalyzerRule::class);
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__.'/../../config/dba.neon',
        ];
    }

    public function testNotUsingIndex(): void
    {
        if ('pdo-pgsql' === getenv('DBA_REFLECTOR')) {
            $this->markTestSkipped('query plan analyzer is not yet implemented for pgsql');
        }

        if ('recording' !== getenv('DBA_MODE')) {
            $this->markTestSkipped('query plan analyzer requires a active database connection');
        }

        $this->numberOfAllowedUnindexedReads = true;
        $this->numberOfRowsNotRequiringIndex = 2;

        $this->analyse([__DIR__.'/data/query-plan-analyzer.php'], [
            [
                "Query is not using an index on table 'ada'.

Consider optimizing the query.
In some cases this is not a problem and this error should be ignored.",
                12,
            ],
            [
                "Query is not using an index on table 'ada'.

Consider optimizing the query.
In some cases this is not a problem and this error should be ignored.",
                17,
            ],
            [
                "Query is not using an index on table 'ada'.

Consider optimizing the query.
In some cases this is not a problem and this error should be ignored.",
                22,
            ],
        ]);
    }
}
