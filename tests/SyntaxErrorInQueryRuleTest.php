<?php

namespace staabm\PHPStanDba\Tests;

use PHPStan\Rules\Rule;
use staabm\PHPStanDba\Rules\SyntaxErrorInQueryRule;
use Symplify\PHPStanExtensions\Testing\AbstractServiceAwareRuleTestCase;

/**
 * @extends AbstractServiceAwareRuleTestCase<SyntaxErrorInQueryRule>
 */
class SyntaxErrorInQueryRuleTest extends AbstractServiceAwareRuleTestCase
{
    protected function getRule(): Rule
    {
        return $this->getRuleFromConfig(SyntaxErrorInQueryRule::class, __DIR__.'/../config/dba.neon');
    }

    public function testSyntaxErrorInQueryRule(): void
    {
        require_once __DIR__.'/data/syntax-error-in-query.php';

        $this->analyse([__DIR__.'/data/syntax-error-in-query.php'], [
            [
                'Query contains a syntax error.',
                11,
            ],
            [
                'Query contains a syntax error.',
                16,
            ],
            [
                'Query contains a syntax error.',
                21,
            ],
            [
                'Query contains a syntax error.',
                26,
            ],
            [
                'Query contains a syntax error.',
                31,
            ],
            [
                'Query contains a syntax error.',
                36,
            ],
        ]);
    }
}
