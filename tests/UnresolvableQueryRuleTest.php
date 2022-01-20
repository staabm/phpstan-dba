<?php

namespace staabm\PHPStanDba\Tests;

use PHPStan\Rules\Rule;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\Rules\SyntaxErrorInPreparedStatementMethodRule;
use Symplify\PHPStanExtensions\Testing\AbstractServiceAwareRuleTestCase;

/**
 * @extends AbstractServiceAwareRuleTestCase<SyntaxErrorInPreparedStatementMethodRule>
 */
class UnresolvableQueryRuleTest extends AbstractServiceAwareRuleTestCase
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
        return $this->getRuleFromConfig(SyntaxErrorInPreparedStatementMethodRule::class, __DIR__.'/config/syntax-error-in-prepared-statement.neon');
    }

    public function testSyntaxErrorInQueryRule(): void
    {
        require_once __DIR__.'/data/unresolvable-statement.php';

        $this->analyse([__DIR__.'/data/unresolvable-statement.php'], [
            [
                "Unresolvable Query: Cannot simulate parameter value for type: mixed.",
                12,
            ],
        ]);
    }
}
