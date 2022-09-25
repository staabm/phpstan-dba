<?php

namespace staabm\PHPStanDba\Tests;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use staabm\PHPStanDba\QueryReflection\MysqliQueryReflector;
use staabm\PHPStanDba\QueryReflection\PdoMysqlQueryReflector;
use staabm\PHPStanDba\QueryReflection\PdoPgSqlQueryReflector;
use staabm\PHPStanDba\Rules\SyntaxErrorInQueryMethodRule;

/**
 * @extends RuleTestCase<SyntaxErrorInQueryMethodRule>
 */
class SyntaxErrorInQueryMethodRuleTest extends RuleTestCase
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
        if (\PHP_VERSION_ID < 70400) {
            self::markTestSkipped('Test requires PHP 7.4.');
        }

        if (MysqliQueryReflector::NAME === getenv('DBA_REFLECTOR')) {
            $expected = [
                [
                    "Query error: You have an error in your SQL syntax; check the manual that corresponds to your MySQL/MariaDB server version for the right syntax to use near 'freigabe1u1 FROM ada LIMIT 0' at line 1 (1064).",
                    11,
                ],
                [
                    "Query error: You have an error in your SQL syntax; check the manual that corresponds to your MySQL/MariaDB server version for the right syntax to use near 'freigabe1u1 FROM ada LIMIT 0' at line 1 (1064).",
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
                    "Query error: Table 'phpstan_dba.unknown_table' doesn't exist (1146).",
                    41,
                ],
                [
                    "Query error: You have an error in your SQL syntax; check the manual that corresponds to your MySQL/MariaDB server version for the right syntax to use near 'FROM ada LIMIT 0' at line 1 (1064).",
                    56,
                ],
                [
                    "Query error: You have an error in your SQL syntax; check the manual that corresponds to your MySQL/MariaDB server version for the right syntax to use near 'freigabe1u1 FROM ada LIMIT 0' at line 1 (1064).",
                    76,
                ],
                [
                    "Query error: You have an error in your SQL syntax; check the manual that corresponds to your MySQL/MariaDB server version for the right syntax to use near 'freigabe1u1 FROM ada LIMIT 0' at line 1 (1064).",
                    82,
                ],
                [
                    "Query error: Unknown column 'asdsa' in 'where clause' (1054).",
                    103,
                ],
                [
                    "Query error: Unknown column 'xy' in 'group statement' (1054).",
                    118,
                ],
            ];
        } elseif (PdoMysqlQueryReflector::NAME === getenv('DBA_REFLECTOR')) {
            $expected = [
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
            ];
        } elseif (PdoPgSqlQueryReflector::NAME === getenv('DBA_REFLECTOR')) {
            $expected = [
                [
                    'Query error: SQLSTATE[42601]: Syntax error: 7 ERROR:  syntax error at or near "freigabe1u1"
LINE 1: SELECT email adaid WHERE gesperrt freigabe1u1 FROM ada LIMIT...
                                          ^ (42601).',
                    11,
                ],
                [
                    'Query error: SQLSTATE[42601]: Syntax error: 7 ERROR:  syntax error at or near "freigabe1u1"
LINE 1: SELECT email adaid WHERE gesperrt freigabe1u1 FROM ada LIMIT...
                                          ^ (42601).',
                    16,
                ],
                [
                    'Query error: SQLSTATE[42703]: Undefined column: 7 ERROR:  column "doesnotexist" does not exist
LINE 1: SELECT doesNotExist, adaid, gesperrt, freigabe1u1 FROM ada L...
               ^ (42703).',
                    21,
                ],
                [
                    'Query error: SQLSTATE[42703]: Undefined column: 7 ERROR:  column "doesnotexist" does not exist
LINE 1: SELECT * FROM ada WHERE doesNotExist=1 LIMIT 0
                                ^ (42703).',
                    26,
                ],
                [
                    'Query error: SQLSTATE[42703]: Undefined column: 7 ERROR:  column "doesnotexist" does not exist
LINE 1: SELECT * FROM ada ORDER BY doesNotExist LIMIT 0
                                   ^ (42703).',
                    31,
                ],
                [
                    'Query error: SQLSTATE[42703]: Undefined column: 7 ERROR:  column "doesnotexist" does not exist
LINE 1: SELECT * FROM ada GROUP BY doesNotExist LIMIT 0
                                   ^ (42703).',
                    36,
                ],
                [
                    'Query error: SQLSTATE[42P01]: Undefined table: 7 ERROR:  relation "unknown_table" does not exist
LINE 1: SELECT * FROM unknown_table LIMIT 0
                      ^ (42P01).',
                    41,
                ],
                [
                    'Query error: SQLSTATE[42601]: Syntax error: 7 ERROR:  syntax error at or near "FROM"
LINE 1: SELECT email, adaid GROUP BY xy FROM ada LIMIT 0
                                        ^ (42601).',
                    56,
                ],
                [
                    'Query error: SQLSTATE[42601]: Syntax error: 7 ERROR:  syntax error at or near "freigabe1u1"
LINE 1: SELECT email adaid WHERE gesperrt freigabe1u1 FROM ada LIMIT...
                                          ^ (42601).',
                    76,
                ],
                [
                    'Query error: SQLSTATE[42601]: Syntax error: 7 ERROR:  syntax error at or near "freigabe1u1"
LINE 1: SELECT email adaid WHERE gesperrt freigabe1u1 FROM ada LIMIT...
                                          ^ (42601).',
                    82,
                ],
                [
                    'Query error: SQLSTATE[42703]: Undefined column: 7 ERROR:  column "asdsa" does not exist
LINE 1: ...mail, adaid, gesperrt, freigabe1u1 FROM ada WHERE asdsa=1 LI...
                                                             ^ (42703).',
                    103,
                ],
                [
                    'Query error: SQLSTATE[42703]: Undefined column: 7 ERROR:  column "xy" does not exist
LINE 1: SELECT email, adaid FROM ada GROUP BY xy LIMIT 0
                                              ^ (42703).',
                    118,
                ],
            ];
        } else {
            throw new \RuntimeException('Unsupported DBA_REFLECTOR '.getenv('DBA_REFLECTOR'));
        }

        require_once __DIR__.'/data/syntax-error-in-query-method.php';

        $this->analyse([__DIR__.'/data/syntax-error-in-query-method.php'], $expected);
    }
}
