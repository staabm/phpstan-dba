<?php

namespace staabm\PHPStanDba\Tests;

use PHPStan\Rules\Rule;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\Rules\PdoStatementExecuteMethodRule;
use staabm\PHPStanDba\UnresolvableQueryException;
use Symplify\PHPStanExtensions\Testing\AbstractServiceAwareRuleTestCase;

/**
 * @extends AbstractServiceAwareRuleTestCase<PdoStatementExecuteMethodRule>
 */
class UnresolvablePdoStatementRuleMysqliReflectorTest extends AbstractServiceAwareRuleTestCase
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
        return $this->getRuleFromConfig(PdoStatementExecuteMethodRule::class, __DIR__.'/../../config/dba.neon');
    }

    public function testSyntaxErrorInQueryRule(): void
    {
        if ('mysqli' !== getenv('DBA_REFLECTOR')) {
            $this->markTestSkipped('Only works with MysqliReflector');
        }

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
