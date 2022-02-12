<?php

namespace staabm\PHPStanDba\Tests;

use PHPStan\Rules\Rule;
use staabm\PHPStanDba\Rules\SyntaxErrorInPreparedStatementMethodRule;
use Symplify\PHPStanExtensions\Testing\AbstractServiceAwareRuleTestCase;

/**
 * @extends AbstractServiceAwareRuleTestCase<SyntaxErrorInPreparedStatementMethodRule>
 */
class SyntaxErrorInPreparedStatementMethodRulePdoReflectorTest extends AbstractServiceAwareRuleTestCase
{
    protected function getRule(): Rule
    {
        return $this->getRuleFromConfig(SyntaxErrorInPreparedStatementMethodRule::class, __DIR__.'/config/syntax-error-in-prepared-statement.neon');
    }

    public function testSyntaxErrorInQueryRule(): void
    {
        if ('pdo' !== getenv('DBA_REFLECTOR')) {
            $this->markTestSkipped('Only works with PdoReflector');
        }

        require_once __DIR__.'/data/syntax-error-in-prepared-statement.php';

        $this->analyse([__DIR__.'/data/syntax-error-in-prepared-statement.php'], [
            [
                "Query error: SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL/MariaDB server version for the right syntax to use near 'freigabe1u1 FROM ada LIMIT 0' at line 1 (42000).",
                12,
            ],
            [
                "Query error: SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL/MariaDB server version for the right syntax to use near 'freigabe1u1 FROM ada LIMIT 0' at line 1 (42000).",
                17,
            ],
            [
                "Query error: SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL/MariaDB server version for the right syntax to use near 'FROM ada LIMIT 0' at line 3 (42000).",
                22,
            ],
            [
                "Query error: SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL/MariaDB server version for the right syntax to use near 'FROM ada LIMIT 0' at line 3 (42000).",
                29,
            ],
            [
                "Query error: SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL/MariaDB server version for the right syntax to use near 'freigabe1u1 FROM ada LIMIT 0' at line 1 (42000).",
                105,
            ],
            [
                "Query error: SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL/MariaDB server version for the right syntax to use near 'freigabe1u1 FROM ada LIMIT 0' at line 1 (42000).",
                106,
            ],
            [
                "Query error: SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL/MariaDB server version for the right syntax to use near 'freigabe1u1 FROM ada LIMIT 0' at line 1 (42000).",
                107,
            ],
            [
                "Query error: SQLSTATE[42S22]: Column not found: 1054 Unknown column 'asdsa' in 'where clause' (42S22).",
                122,
            ],
            [
                'Value :gesperrt is given, but the query does not contain this placeholder.',
                137,
            ],
            [
                'Query expects placeholder :name, but it is missing from values given.',
                307,
            ],
            [
                "Query error: SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL/MariaDB server version for the right syntax to use near 'gesperrt freigabe1u1 FROM ada LIMIT 0' at line 1 (42000).",
                319,
            ],
        ]);
    }
}
