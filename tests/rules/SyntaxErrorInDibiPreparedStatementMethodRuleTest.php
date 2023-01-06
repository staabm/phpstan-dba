<?php

namespace staabm\PHPStanDba\Tests;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use staabm\PHPStanDba\QueryReflection\MysqliQueryReflector;
use staabm\PHPStanDba\QueryReflection\PdoMysqlQueryReflector;
use staabm\PHPStanDba\QueryReflection\PdoPgSqlQueryReflector;
use staabm\PHPStanDba\Rules\SyntaxErrorInDibiPreparedStatementMethodRule;

/**
 * @extends RuleTestCase<SyntaxErrorInDibiPreparedStatementMethodRule>
 */
class SyntaxErrorInDibiPreparedStatementMethodRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(SyntaxErrorInDibiPreparedStatementMethodRule::class);
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
            $expectedErrors = [
                [
                    "Query error: You have an error in your SQL syntax; check the manual that corresponds to your MySQL/MariaDB server version for the right syntax to use near 'FROM ada LIMIT 0' at line 1 (1064).",
                    13,
                ],
                [
                    "Query error: You have an error in your SQL syntax; check the manual that corresponds to your MySQL/MariaDB server version for the right syntax to use near 'FROM ada LIMIT 0' at line 1 (1064).",
                    19,
                ],
                [
                    "Query error: You have an error in your SQL syntax; check the manual that corresponds to your MySQL/MariaDB server version for the right syntax to use near 'FROM ada LIMIT 0' at line 1 (1064).",
                    25,
                ],
                [
                    'fetchSingle requires exactly 1 selected column, got 2.',
                    26,
                ],
                [
                    "Query error: You have an error in your SQL syntax; check the manual that corresponds to your MySQL/MariaDB server version for the right syntax to use near 'FROM ada LIMIT 0' at line 1 (1064).",
                    32,
                ],
                [
                    'fetchPairs requires exactly 2 selected columns, got 1.',
                    33,
                ],
                [
                    'Query expects 1 placeholder, but no values are given.',
                    39,
                ],
                [
                    'Query expects 0 placeholder, but 1 value is given.',
                    40,
                ],
                [
                    "Query error: Table 'phpstan_dba.adasfd' doesn't exist (1146).",
                    66,
                ],
                [
                    "Query error: Table 'phpstan_dba.adasfd' doesn't exist (1146).",
                    67,
                ],
                [
                    "Query error: Table 'phpstan_dba.adasfd' doesn't exist (1146).",
                    68,
                ],
                [
                    "Query error: Table 'phpstan_dba.adasfd' doesn't exist (1146).",
                    69,
                ],
            ];
        } elseif (PdoPgSqlQueryReflector::NAME === getenv('DBA_REFLECTOR')) {
            $expectedErrors = [
                [
                    'Query error: SQLSTATE[42601]: Syntax error: 7 ERROR:  syntax error at or near "FROM"
LINE 1: SELECT email adaid WHERE gesperrt FROM ada LIMIT 0
                                          ^ (42601).',
                    13,
                ],
                [
                    'Query error: SQLSTATE[42601]: Syntax error: 7 ERROR:  syntax error at or near "FROM"
LINE 1: SELECT email adaid WHERE gesperrt FROM ada LIMIT 0
                                          ^ (42601).',
                    19,
                ],
                [
                    'Query error: SQLSTATE[42601]: Syntax error: 7 ERROR:  syntax error at or near "FROM"
LINE 1: SELECT email adaid WHERE gesperrt FROM ada LIMIT 0
                                          ^ (42601).',
                    25,
                ],
                [
                    'fetchSingle requires exactly 1 selected column, got 2.',
                    26,
                ],
                [
                    'Query error: SQLSTATE[42601]: Syntax error: 7 ERROR:  syntax error at or near "FROM"
LINE 1: SELECT email adaid WHERE gesperrt FROM ada LIMIT 0
                                          ^ (42601).',
                    32,
                ],
                [
                    'fetchPairs requires exactly 2 selected columns, got 1.',
                    33,
                ],
                [
                    'Query expects 1 placeholder, but no values are given.',
                    39,
                ],
                [
                    'Query expects 0 placeholder, but 1 value is given.',
                    40,
                ],
                [
                    'Query error: SQLSTATE[42601]: Syntax error: 7 ERROR:  zero-length delimited identifier at or near """"
LINE 1: UPDATE ada set email = ""
                               ^ (42601).',
                    56,
                ],
                [
                    'Query error: SQLSTATE[42P01]: Undefined table: 7 ERROR:  relation "adasfd" does not exist
LINE 1: DELETE from adasfd
                    ^ (42P01).',
                    66,
                ],
                [
                    'Query error: SQLSTATE[42601]: Syntax error: 7 ERROR:  zero-length delimited identifier at or near """"
LINE 1: UPDATE adasfd SET email = ""
                                  ^ (42601).',
                    67,
                ],
                [
                    'Query error: SQLSTATE[42601]: Syntax error: 7 ERROR:  syntax error at or near "SET"
LINE 1: INSERT into adasfd SET email="sdf"
                           ^ (42601).',
                    68,
                ],
                [
                    'Query error: SQLSTATE[42601]: Syntax error: 7 ERROR:  syntax error at or near "REPLACE"
LINE 1: REPLACE into adasfd SET email="sdf"
        ^ (42601).',
                    69,
                ],
            ];
        } elseif (PdoMysqlQueryReflector::NAME === getenv('DBA_REFLECTOR')) {
            $expectedErrors = [
                [
                    "Query error: SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL/MariaDB server version for the right syntax to use near 'FROM ada LIMIT 0' at line 1 (42000).",
                    13,
                ],
                [
                    "Query error: SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL/MariaDB server version for the right syntax to use near 'FROM ada LIMIT 0' at line 1 (42000).",
                    19,
                ],
                [
                    "Query error: SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL/MariaDB server version for the right syntax to use near 'FROM ada LIMIT 0' at line 1 (42000).",
                    25,
                ],
                [
                    'fetchSingle requires exactly 1 selected column, got 2.',
                    26,
                ],
                [
                    "Query error: SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL/MariaDB server version for the right syntax to use near 'FROM ada LIMIT 0' at line 1 (42000).",
                    32,
                ],
                [
                    'fetchPairs requires exactly 2 selected columns, got 1.',
                    33,
                ],
                [
                    'Query expects 1 placeholder, but no values are given.',
                    39,
                ],
                [
                    'Query expects 0 placeholder, but 1 value is given.',
                    40,
                ],
                [
                    "Query error: SQLSTATE[42S02]: Base table or view not found: 1146 Table 'phpstan_dba.adasfd' doesn't exist (42S02).",
                    66,
                ],
                [
                    "Query error: SQLSTATE[42S02]: Base table or view not found: 1146 Table 'phpstan_dba.adasfd' doesn't exist (42S02).",
                    67,
                ],
                [
                    "Query error: SQLSTATE[42S02]: Base table or view not found: 1146 Table 'phpstan_dba.adasfd' doesn't exist (42S02).",
                    68,
                ],
                [
                    "Query error: SQLSTATE[42S02]: Base table or view not found: 1146 Table 'phpstan_dba.adasfd' doesn't exist (42S02).",
                    69,
                ],
            ];
        } else {
            throw new \RuntimeException('Unsupported DBA_REFLECTOR '.getenv('DBA_REFLECTOR'));
        }

        require_once __DIR__.'/data/syntax-error-in-dibi-prepared-statement.php';
        $this->analyse([__DIR__.'/data/syntax-error-in-dibi-prepared-statement.php'], $expectedErrors);
    }
}
