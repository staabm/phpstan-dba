<?php

namespace staabm\PHPStanDba\Tests;

use PHPStan\Rules\Rule;
use staabm\PHPStanDba\Rules\SyntaxErrorInQueryFunctionRule;
use Symplify\PHPStanExtensions\Testing\AbstractServiceAwareRuleTestCase;

/**
 * @extends AbstractServiceAwareRuleTestCase<SyntaxErrorInQueryFunctionRule>
 */
class SyntaxErrorInQueryFunctionRuleTest extends AbstractServiceAwareRuleTestCase
{
    protected function getRule(): Rule
    {
        return $this->getRuleFromConfig(SyntaxErrorInQueryFunctionRule::class, __DIR__.'/../config/dba.neon');
    }

    public function testSyntaxErrorInQueryRule(): void
    {
        require_once __DIR__.'/data/syntax-error-in-query-function.php';

        $this->analyse([__DIR__.'/data/syntax-error-in-query-function.php'], [
            [
                'Query contains a syntax error.',
                11,
            ],
            [
                'Query contains a syntax error.',
                21,
            ],
        ]);
    }
}
