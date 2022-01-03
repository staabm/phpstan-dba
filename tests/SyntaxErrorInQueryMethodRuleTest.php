<?php

namespace staabm\PHPStanDba\Tests;

use PHPStan\Rules\Rule;
use staabm\PHPStanDba\Rules\SyntaxErrorInQueryMethodRule;
use Symplify\PHPStanExtensions\Testing\AbstractServiceAwareRuleTestCase;

/**
 * @extends AbstractServiceAwareRuleTestCase<SyntaxErrorInQueryMethodRule>
 */
class SyntaxErrorInQueryMethodRuleTest extends AbstractServiceAwareRuleTestCase
{
    protected function getRule(): Rule
    {
        return $this->getRuleFromConfig(SyntaxErrorInQueryMethodRule::class, __DIR__.'/../config/dba.neon');
    }

    public function testSyntaxErrorInQueryRule(): void
    {
        require_once __DIR__.'/data/syntax-error-in-query.php';

        $this->analyse([__DIR__.'/data/syntax-error-in-query.php'], [
            [
                "Query error: You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near 'freigabe1u1 FROM ada' at line 1 (1064).",
                11,
            ],
            [
                "Query error: You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near 'freigabe1u1 FROM ada' at line 1 (1064).",
                16,
            ],
            [
                "Query error: Unknown column 'doesNotExist' in 'field list' (1054).",
                21,
            ],
            [
                "Query error: Unknown column 'doesNotExist' in 'where clause' (1054).",
                26,
            ],
            [
                "Query error: Unknown column 'doesNotExist' in 'order clause' (1054).",
                31,
            ],
            [
                "Query error: Unknown column 'doesNotExist' in 'group statement' (1054).",
                36,
            ],
            [
                "Query error: Table 'phpstan_dba.unknownTable' doesn't exist (1146).",
                41,
            ],
        ]);
    }
}
