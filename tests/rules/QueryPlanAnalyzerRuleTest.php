<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Tests;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\Rules\QueryPlanAnalyzerRule;
use staabm\PHPStanDba\UnresolvableQueryMixedTypeException;
use staabm\PHPStanDba\UnresolvableQueryStringTypeException;

/**
 * @extends RuleTestCase<QueryPlanAnalyzerRule>
 */
class QueryPlanAnalyzerRuleTest extends RuleTestCase
{
    /**
     * @var bool
     */
    private $numberOfAllowedUnindexedReads;

    /**
     * @var positive-int
     */
    private $numberOfRowsNotRequiringIndex;

    /**
     * @var bool
     */
    private $debugMode = false;

    protected function tearDown(): void
    {
        QueryReflection::getRuntimeConfiguration()->debugMode(false);
        QueryReflection::getRuntimeConfiguration()->analyzeQueryPlans(false);
    }

    protected function getRule(): Rule
    {
        QueryReflection::getRuntimeConfiguration()->debugMode($this->debugMode);
        QueryReflection::getRuntimeConfiguration()->analyzeQueryPlans($this->numberOfAllowedUnindexedReads, $this->numberOfRowsNotRequiringIndex);

        return self::getContainer()->getByType(QueryPlanAnalyzerRule::class);
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../../config/dba.neon',
        ];
    }

    public function testNotUsingIndex(): void
    {
        if ('pdo-pgsql' === $_ENV['DBA_REFLECTOR']) {
            self::markTestSkipped('query plan analyzer is not yet implemented for pgsql');
        }

        if (ReflectorFactory::MODE_RECORDING !== getenv('DBA_MODE')) {
            self::markTestSkipped('query plan analyzer requires a active database connection');
        }

        $this->numberOfAllowedUnindexedReads = true;
        $this->numberOfRowsNotRequiringIndex = 2;

        $proposal = "\n\nConsider optimizing the query.\nIn some cases this is not a problem and this error should be ignored.";
        $tip = 'see Mysql Docs https://dev.mysql.com/doc/refman/8.0/en/select-optimization.html';

        $this->analyse([__DIR__ . '/data/query-plan-analyzer.php'], [
            [
                "Query is not using an index on table 'ada'." . $proposal,
                12,
                $tip,
            ],
            [
                "Query is not using an index on table 'ada'." . $proposal,
                17,
                $tip,
            ],
            [
                "Query is not using an index on table 'ada'." . $proposal,
                22,
                $tip,
            ],
            [
                "Query is not using an index on table 'ada'." . $proposal,
                23,
                $tip,
            ],
            [
                "Query is not using an index on table 'ada'." . $proposal,
                28,
                $tip,
            ],
        ]);
    }

    public function testNotUsingIndexInDebugMode(): void
    {
        if ('pdo-pgsql' === $_ENV['DBA_REFLECTOR']) {
            self::markTestSkipped('query plan analyzer is not yet implemented for pgsql');
        }

        if (ReflectorFactory::MODE_RECORDING !== getenv('DBA_MODE')) {
            self::markTestSkipped('query plan analyzer requires a active database connection');
        }

        $this->debugMode = true;
        $this->numberOfAllowedUnindexedReads = true;
        $this->numberOfRowsNotRequiringIndex = 2;

        $proposal = "\n\nConsider optimizing the query.\nIn some cases this is not a problem and this error should be ignored.";
        $tip = 'see Mysql Docs https://dev.mysql.com/doc/refman/8.0/en/select-optimization.html';

        $this->analyse([__DIR__ . '/data/query-plan-analyzer.php'], [
            [
                "Query is not using an index on table 'ada'." . $proposal . "\n\nSimulated query: EXPLAIN SELECT * FROM `ada` WHERE email = 'test@example.com'",
                12,
                $tip,
            ],
            [
                "Query is not using an index on table 'ada'." . $proposal . "\n\nSimulated query: EXPLAIN SELECT *,adaid FROM `ada` WHERE email = 'test@example.com'",
                17,
                $tip,
            ],
            [
                "Query is not using an index on table 'ada'." . $proposal . "\n\nSimulated query: EXPLAIN SELECT * FROM ada WHERE email = '1970-01-01'",
                22,
                $tip,
            ],
            [
                "Query is not using an index on table 'ada'." . $proposal . "\n\nSimulated query: EXPLAIN SELECT * FROM ada WHERE email = '1970-01-01'",
                23,
                $tip,
            ],
            [
                "Query is not using an index on table 'ada'." . $proposal . "\n\nSimulated query: EXPLAIN SELECT * FROM ada WHERE email = '1970-01-01'",
                28,
                $tip,
            ],
            [
                'Unresolvable Query: Cannot simulate parameter value for type: mixed.',
                61,
                UnresolvableQueryMixedTypeException::getTip(),
            ],
            [
                'Unresolvable Query: Cannot resolve query with variable type: string.',
                67,
                UnresolvableQueryStringTypeException::getTip(),
            ],
            [
                'Unresolvable Query: Cannot resolve query with variable type: string.',
                70,
                UnresolvableQueryStringTypeException::getTip(),
            ],
            [
                'Unresolvable Query: Cannot resolve query with variable type: string.',
                73,
                UnresolvableQueryStringTypeException::getTip(),
            ],
        ]);
    }
}
