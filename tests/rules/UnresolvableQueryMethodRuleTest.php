<?php

namespace staabm\PHPStanDba\Tests;

use PHPStan\Rules\Rule;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\Rules\SyntaxErrorInQueryMethodRule;
use staabm\PHPStanDba\UnresolvableQueryException;
use Symplify\PHPStanExtensions\Testing\AbstractServiceAwareRuleTestCase;

/**
 * @extends AbstractServiceAwareRuleTestCase<SyntaxErrorInQueryMethodRule>
 */
class UnresolvableQueryMethodRuleTest extends AbstractServiceAwareRuleTestCase
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
        return $this->getRuleFromConfig(SyntaxErrorInQueryMethodRule::class, __DIR__.'/../../config/dba.neon');
    }

    public function testSyntaxErrorInQueryRule(): void
    {
        require_once __DIR__.'/data/unresolvable-query-in-method.php';

        $this->analyse([__DIR__.'/data/unresolvable-query-in-method.php'], [
            [
                'Unresolvable Query: Cannot simulate parameter value for type: mixed.',
                11,
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
