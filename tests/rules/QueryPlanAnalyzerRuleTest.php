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

    protected function tearDown(): void
    {
        QueryReflection::getRuntimeConfiguration()->analyzeQueryPlans(false);
    }

    protected function getRule(): Rule
    {
        QueryReflection::getRuntimeConfiguration()->analyzeQueryPlans($this->numberOfAllowedUnindexedReads);

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

        $this->analyse([__DIR__.'/data/query-plan-analyzer.php'], [
            [
                'Query plan analyzer: table "ada" is not using an index',
                12,
            ],
            [
                'Query plan analyzer: table "ada" is not using an index',
                17,
            ],
            [
                'Query plan analyzer: table "ada" is not using an index',
                22,
            ],
        ]);
    }
}
