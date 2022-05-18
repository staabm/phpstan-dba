<?php

namespace staabm\PHPStanDba\Tests;

use PHPStan\Rules\Rule;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\Rules\SyntaxErrorInQueryFunctionRule;
use staabm\PHPStanDba\UnresolvableQueryException;
use Symplify\PHPStanExtensions\Testing\AbstractServiceAwareRuleTestCase;

/**
 * @extends AbstractServiceAwareRuleTestCase<SyntaxErrorInQueryFunctionRule>
 */
class UnresolvableQueryFunctionRuleTest extends AbstractServiceAwareRuleTestCase
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
        return $this->getRuleFromConfig(SyntaxErrorInQueryFunctionRule::class, __DIR__.'/../../config/dba.neon');
    }

    public function testSyntaxErrorInQueryRule(): void
    {
        require_once __DIR__.'/data/unresolvable-query-in-function.php';

        $this->analyse([__DIR__.'/data/unresolvable-query-in-function.php'], [
            [
                'Unresolvable Query: Cannot simulate parameter value for type: mixed.',
                9,
                UnresolvableQueryException::RULE_TIP,
            ],
            [
                'Unresolvable Query: Cannot simulate parameter value for type: mixed.',
                15,
                UnresolvableQueryException::RULE_TIP,
            ],
        ]);
    }
}
