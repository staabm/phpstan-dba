<?php

namespace staabm\PHPStanDba\Tests;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use staabm\PHPStanDba\QueryReflection\MysqliQueryReflector;
use staabm\PHPStanDba\QueryReflection\PdoMysqlQueryReflector;
use staabm\PHPStanDba\QueryReflection\PdoPgSqlQueryReflector;
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
        if (\PHP_VERSION_ID < 70400) {
            self::markTestSkipped('Test requires PHP 7.4.');
        }

        if (MysqliQueryReflector::NAME === getenv('DBA_REFLECTOR')) {
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
        } elseif (PdoPgSqlQueryReflector::NAME === getenv('DBA_REFLECTOR')) {
            $expectedErrors = [
                [
                    'Query error: SQLSTATE[42601]: Syntax error: 7 ERROR:  syntax error at or near "freigabe1u1"
LINE 1: SELECT email adaid WHERE gesperrt freigabe1u1 FROM ada LIMIT...
                                          ^ (42601).',
                    12,
                ],
                [
                    'Query error: SQLSTATE[42601]: Syntax error: 7 ERROR:  syntax error at or near "freigabe1u1"
LINE 1: SELECT email adaid WHERE gesperrt freigabe1u1 FROM ada LIMIT...
                                          ^ (42601).',
                    17,
                ],
                [
                    'Query error: SQLSTATE[42601]: Syntax error: 7 ERROR:  syntax error at or near "FROM"
LINE 3:             FROM ada LIMIT 0
                    ^ (42601).',
                    22,
                ],
                [
                    'Query error: SQLSTATE[42601]: Syntax error: 7 ERROR:  syntax error at or near "FROM"
LINE 3:             FROM ada LIMIT 0
                    ^ (42601).',
                    29,
                ],
                [
                    'Query error: SQLSTATE[42601]: Syntax error: 7 ERROR:  syntax error at or near "freigabe1u1"
LINE 1: SELECT email adaid WHERE gesperrt freigabe1u1 FROM ada LIMIT...
                                          ^ (42601).',
                    105,
                ],
                [
                    'Query error: SQLSTATE[42601]: Syntax error: 7 ERROR:  syntax error at or near "freigabe1u1"
LINE 1: SELECT email adaid WHERE gesperrt freigabe1u1 FROM ada LIMIT...
                                          ^ (42601).',
                    106,
                ],
                [
                    'Query error: SQLSTATE[42601]: Syntax error: 7 ERROR:  syntax error at or near "freigabe1u1"
LINE 1: SELECT email adaid WHERE gesperrt freigabe1u1 FROM ada LIMIT...
                                          ^ (42601).',
                    107,
                ],
                [
                    'Query error: SQLSTATE[42703]: Undefined column: 7 ERROR:  column "asdsa" does not exist
LINE 1: ...mail, adaid, gesperrt, freigabe1u1 FROM ada WHERE asdsa=\'1\' ...
                                                             ^ (42703).',
                    122,
                ],
                [
                    'Value :gesperrt is given, but the query does not contain this placeholder.
162: Query error: SQLSTATE[42703]: Undefined column: 7 ERROR:  column ":gesperrt%" does not exist
LINE 1: SELECT adaid FROM ada WHERE email LIKE ":gesperrt%" LIMIT 0
                                               ^
HINT:  Perhaps you meant to reference the column "ada.gesperrt". (42703).',
                    137,
                ],
                [
                    'Query expects placeholder :name, but it is missing from values given.',
                    307,
                ],
                [
                    'Query error: SQLSTATE[42601]: Syntax error: 7 ERROR:  syntax error at or near "gesperrt"
LINE 1: SELECT email adaid gesperrt freigabe1u1 FROM ada LIMIT 0
                           ^ (42601).',
                    319,
                ],
            ];
        } elseif (PdoMysqlQueryReflector::NAME === getenv('DBA_REFLECTOR')) {
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
