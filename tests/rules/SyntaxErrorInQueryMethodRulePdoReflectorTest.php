<?php

namespace staabm\PHPStanDba\Tests;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use staabm\PHPStanDba\Rules\SyntaxErrorInQueryMethodRule;

/**
 * @extends RuleTestCase<SyntaxErrorInQueryMethodRule>
 */
class SyntaxErrorInQueryMethodRulePdoReflectorTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(SyntaxErrorInQueryMethodRule::class);
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__.'/../../config/dba.neon',
        ];
    }

    public function testSyntaxErrorInQueryRule(): void
    {
        if ('pdo' !== getenv('DBA_REFLECTOR')) {
            $this->markTestSkipped('Only works with MysqliReflector');
        }

        require_once __DIR__.'/data/syntax-error-in-query-method.php';

        $this->analyse([__DIR__.'/data/syntax-error-in-query-method.php'], [
            [
                "Query error: SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL/MariaDB server version for the right syntax to use near 'freigabe1u1 FROM ada LIMIT 0' at line 1 (42000).",
                11,
            ],
            [
                "Query error: SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL/MariaDB server version for the right syntax to use near 'freigabe1u1 FROM ada LIMIT 0' at line 1 (42000).",
                16,
            ],
            [
                "Query error: SQLSTATE[42S22]: Column not found: 1054 Unknown column 'doesNotExist' in 'field list' (42S22).",
                21,
            ],
            [
                "Query error: SQLSTATE[42S22]: Column not found: 1054 Unknown column 'doesNotExist' in 'where clause' (42S22).",
                26,
            ],
            [
                "Query error: SQLSTATE[42S22]: Column not found: 1054 Unknown column 'doesNotExist' in 'order clause' (42S22).",
                31,
            ],
            [
                "Query error: SQLSTATE[42S22]: Column not found: 1054 Unknown column 'doesNotExist' in 'group statement' (42S22).",
                36,
            ],
            [
                "Query error: SQLSTATE[42S02]: Base table or view not found: 1146 Table 'phpstan_dba.unknown_table' doesn't exist (42S02).",
                41,
            ],
            [
                "Query error: SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL/MariaDB server version for the right syntax to use near 'FROM ada LIMIT 0' at line 1 (42000).",
                56,
            ],
            [
                "Query error: SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL/MariaDB server version for the right syntax to use near 'freigabe1u1 FROM ada LIMIT 0' at line 1 (42000).",
                76,
            ],
            [
                "Query error: SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL/MariaDB server version for the right syntax to use near 'freigabe1u1 FROM ada LIMIT 0' at line 1 (42000).",
                82,
            ],
            [
                "Query error: SQLSTATE[42S22]: Column not found: 1054 Unknown column 'asdsa' in 'where clause' (42S22).",
                103,
            ],
            [
                "Query error: SQLSTATE[42S22]: Column not found: 1054 Unknown column 'xy' in 'group statement' (42S22).",
                118,
            ],
        ]);
    }
}
