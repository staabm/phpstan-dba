<?php

namespace staabm\PHPStanDba\Tests;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\Rules\SyntaxErrorInPreparedStatementMethodRule;
use staabm\PHPStanDba\UnresolvableQueryException;

/**
 * @extends RuleTestCase<SyntaxErrorInPreparedStatementMethodRule>
 */
class UnresolvablePreparedStatementRuleTest extends RuleTestCase
{
    protected function setUp()
    {
        QueryReflection::getRuntimeConfiguration()->debugMode(true);
    }

    protected function tearDown()
    {
        QueryReflection::getRuntimeConfiguration()->debugMode(false);
    }

    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(SyntaxErrorInPreparedStatementMethodRule::class);
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__.'/config/syntax-error-in-prepared-statement.neon',
        ];
    }

    public function testSyntaxErrorInQueryRule()
    {
        require_once __DIR__.'/data/unresolvable-statement.php';

        $this->analyse([__DIR__.'/data/unresolvable-statement.php'], [
            [
                'Unresolvable Query: Cannot simulate parameter value for type: mixed.',
                11,
                UnresolvableQueryException::RULE_TIP,
            ],
        ]);
    }
}
