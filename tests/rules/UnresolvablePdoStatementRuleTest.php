<?php

namespace staabm\PHPStanDba\Tests;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\Rules\PdoStatementExecuteMethodRule;
use staabm\PHPStanDba\UnresolvableQueryException;

/**
 * @extends RuleTestCase<PdoStatementExecuteMethodRule>
 */
class UnresolvablePdoStatementRuleTest extends RuleTestCase
{
    protected function setUp(): void
    {
        QueryReflection::getRuntimeConfiguration()->debugMode(true);
    }

    protected function tearDown(): void
    {
        QueryReflection::getRuntimeConfiguration()->debugMode(false);
    }

    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(PdoStatementExecuteMethodRule::class);
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__.'/../../config/dba.neon',
        ];
    }

    public function testSyntaxErrorInQueryRule(): void
    {
        require_once __DIR__.'/data/unresolvable-pdo-statement.php';

        $this->analyse([__DIR__.'/data/unresolvable-pdo-statement.php'], [
            [
                'Unresolvable Query: Cannot simulate parameter value for type: mixed.',
                13,
                UnresolvableQueryException::RULE_TIP,
            ],
            [
                'Unresolvable Query: Cannot simulate parameter value for type: mixed.',
                17,
                UnresolvableQueryException::RULE_TIP,
            ],
        ]);
    }
}
