<?php

namespace staabm\PHPStanDba\Tests;

use PHPStan\Rules\Rule;
use staabm\PHPStanDba\Rules\SyntaxErrorInQueryFunctionRule;
use Symplify\PHPStanExtensions\Testing\AbstractServiceAwareRuleTestCase;

/**
 * @extends AbstractServiceAwareRuleTestCase<SyntaxErrorInQueryFunctionRule>
 */
class SyntaxErrorInQueryFunctionRulePdoReflectorTest extends AbstractServiceAwareRuleTestCase
{
    protected function getRule(): Rule
    {
        return $this->getRuleFromConfig(SyntaxErrorInQueryFunctionRule::class, __DIR__.'/../../config/dba.neon');
    }

    public function testSyntaxErrorInQueryRule(): void
    {
        if ('pdo' !== getenv('DBA_REFLECTOR')) {
            $this->markTestSkipped('Only works with MysqliReflector');
        }

        require_once __DIR__.'/data/syntax-error-in-query-function.php';

        $this->analyse([__DIR__.'/data/syntax-error-in-query-function.php'], [
            [
                "Query error: SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL/MariaDB server version for the right syntax to use near 'freigabe1u1 FROM ada LIMIT 0' at line 1 (42000).",
                11,
            ],
            [
                "Query error: SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL/MariaDB server version for the right syntax to use near 'freigabe1u1 FROM ada LIMIT 0' at line 1 (42000).",
                21,
            ],
            [
                "Query error: SQLSTATE[42S22]: Column not found: 1054 Unknown column 'asdsa' in 'where clause' (42S22).",
                41,
            ],
        ]);
    }
}
