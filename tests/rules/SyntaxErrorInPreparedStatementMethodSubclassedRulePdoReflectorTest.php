<?php

namespace staabm\PHPStanDba\Tests;

use PHPStan\Rules\Rule;
use staabm\PHPStanDba\Rules\SyntaxErrorInPreparedStatementMethodRule;
use Symplify\PHPStanExtensions\Testing\AbstractServiceAwareRuleTestCase;

/**
 * @extends AbstractServiceAwareRuleTestCase<SyntaxErrorInPreparedStatementMethodRule>
 */
class SyntaxErrorInPreparedStatementMethodSubclassedRulePdoReflectorTest extends AbstractServiceAwareRuleTestCase
{
    protected function getRule(): Rule
    {
        return $this->getRuleFromConfig(SyntaxErrorInPreparedStatementMethodRule::class, __DIR__.'/config/subclassed-method-rule.neon');
    }

    public function testSyntaxErrorInQueryRule(): void
    {
        if ('pdo' !== getenv('DBA_REFLECTOR')) {
            $this->markTestSkipped('Only works with MysqliReflector');
        }

        require_once __DIR__.'/data/syntax-error-in-method-subclassed.php';

        $this->analyse([__DIR__.'/data/syntax-error-in-method-subclassed.php'], [
            [
                "Query error: SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL/MariaDB server version for the right syntax to use near 'with syntax error GROUPY by x LIMIT 0' at line 1 (42000).",
                12,
            ],
            [
                "Query error: SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL/MariaDB server version for the right syntax to use near 'freigabe1u1 FROM ada LIMIT 0' at line 1 (42000).",
                18,
            ],
            [
                "Query error: SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL/MariaDB server version for the right syntax to use near 'FROM ada LIMIT 0' at line 3 (42000).",
                20,
            ],
        ]);
    }
}
