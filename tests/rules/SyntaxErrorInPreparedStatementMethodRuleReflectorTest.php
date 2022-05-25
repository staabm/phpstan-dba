<?php

namespace staabm\PHPStanDba\Tests;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use staabm\PHPStanDba\Rules\SyntaxErrorInPreparedStatementMethodRule;

/**
 * @extends RuleTestCase<SyntaxErrorInPreparedStatementMethodRule>
 */
class SyntaxErrorInPreparedStatementMethodRuleReflectorTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(SyntaxErrorInPreparedStatementMethodRule::class);
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__.'/config/syntax-error-in-prepared-statement.neon',
        ];
    }

    public function testSyntaxErrorInQueryRule(): void
    {
        if ('mysqli' === getenv('DBA_REFLECTOR')) {
            $expectedErrors = [
            [
                "Query error: You have an error in your SQL syntax; check the manual that corresponds to your MySQL/MariaDB server version for the right syntax to use near 'freigabe1u1 FROM ada LIMIT 0' at line 1 (1064).",
                12,
            ],
            [
                "Query error: You have an error in your SQL syntax; check the manual that corresponds to your MySQL/MariaDB server version for the right syntax to use near 'freigabe1u1 FROM ada LIMIT 0' at line 1 (1064).",
                17,
            ],
            [
                "Query error: You have an error in your SQL syntax; check the manual that corresponds to your MySQL/MariaDB server version for the right syntax to use near 'FROM ada LIMIT 0' at line 3 (1064).",
                22,
            ],
            [
                "Query error: You have an error in your SQL syntax; check the manual that corresponds to your MySQL/MariaDB server version for the right syntax to use near 'FROM ada LIMIT 0' at line 3 (1064).",
                29,
            ],
            [
                "Query error: You have an error in your SQL syntax; check the manual that corresponds to your MySQL/MariaDB server version for the right syntax to use near 'freigabe1u1 FROM ada LIMIT 0' at line 1 (1064).",
                105,
            ],
            [
                "Query error: You have an error in your SQL syntax; check the manual that corresponds to your MySQL/MariaDB server version for the right syntax to use near 'freigabe1u1 FROM ada LIMIT 0' at line 1 (1064).",
                106,
            ],
            [
                "Query error: You have an error in your SQL syntax; check the manual that corresponds to your MySQL/MariaDB server version for the right syntax to use near 'freigabe1u1 FROM ada LIMIT 0' at line 1 (1064).",
                107,
            ],
            [
                "Query error: Unknown column 'asdsa' in 'where clause' (1054).",
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
                "Query error: You have an error in your SQL syntax; check the manual that corresponds to your MySQL/MariaDB server version for the right syntax to use near 'gesperrt freigabe1u1 FROM ada LIMIT 0' at line 1 (1064).",
                319,
            ],
        ];
        } elseif ('pdo-mysql' === getenv('DBA_REFLECTOR')) {
            $expectedErrors = [
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
            ];
        } elseif ('pdo-pgsql' === getenv('DBA_REFLECTOR')) {
            $expectedErrors = [
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
            ];
        } else {
            throw new \RuntimeException('Unsupported DBA_REFLECTOR '.getenv('DBA_REFLECTOR'));
        }

        require_once __DIR__.'/data/syntax-error-in-prepared-statement.php';

        $this->analyse([__DIR__.'/data/syntax-error-in-prepared-statement.php'], $expectedErrors);
    }
}
