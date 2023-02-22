<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Tests;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use staabm\PHPStanDba\QueryReflection\MysqliQueryReflector;
use staabm\PHPStanDba\QueryReflection\PdoMysqlQueryReflector;
use staabm\PHPStanDba\QueryReflection\PdoPgSqlQueryReflector;
use staabm\PHPStanDba\Rules\SyntaxErrorInQueryFunctionRule;

/**
 * @extends RuleTestCase<SyntaxErrorInQueryFunctionRule>
 */
class SyntaxErrorInQueryFunctionRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(SyntaxErrorInQueryFunctionRule::class);
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../../config/dba.neon',
        ];
    }

    public function testSyntaxErrorInQueryRule(): void
    {
        if (\PHP_VERSION_ID < 70400) {
            self::markTestSkipped('Test requires PHP 7.4.');
        }

        $this->analyse([__DIR__ . '/data/syntax-error-in-query-function.php'], $this->getExpectedErrors());
    }

    /**
     * @return list<array{string, int}>
     */
    public function getExpectedErrors(): array
    {
        $dbaReflector = getenv('DBA_REFLECTOR');

        switch ($dbaReflector) {
            case MysqliQueryReflector::NAME:
                return [
                    [
                        "Query error: You have an error in your SQL syntax; check the manual that corresponds to your MySQL/MariaDB server version for the right syntax to use near 'freigabe1u1 FROM ada LIMIT 0' at line 1 (1064).",
                        9,
                    ],
                    [
                        "Query error: You have an error in your SQL syntax; check the manual that corresponds to your MySQL/MariaDB server version for the right syntax to use near 'freigabe1u1 FROM ada LIMIT 0' at line 1 (1064).",
                        19,
                    ],
                    [
                        "Query error: Unknown column 'asdsa' in 'where clause' (1054).",
                        39,
                    ],
                ];
            case PdoPgSqlQueryReflector::NAME:
                return [
                    [
                        <<<TEXT
Query error: SQLSTATE[42601]: Syntax error: 7 ERROR:  syntax error at or near "freigabe1u1"
LINE 1: SELECT email adaid WHERE gesperrt freigabe1u1 FROM ada LIMIT...
                                          ^ (42601).
TEXT
                        ,
                        9,
                    ],
                    [
                        <<<TEXT
Query error: SQLSTATE[42601]: Syntax error: 7 ERROR:  syntax error at or near "freigabe1u1"
LINE 1: SELECT email adaid WHERE gesperrt freigabe1u1 FROM ada LIMIT...
                                          ^ (42601).
TEXT
                        ,
                        19,
                    ],
                    [
                        <<<TEXT
Query error: SQLSTATE[42703]: Undefined column: 7 ERROR:  column "asdsa" does not exist
LINE 1: ...mail, adaid, gesperrt, freigabe1u1 FROM ada WHERE asdsa=1 LI...
                                                             ^ (42703).
TEXT
                        ,
                        39,
                    ],
                ];
            case PdoMysqlQueryReflector::NAME:
                return [
                    [
                        "Query error: SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL/MariaDB server version for the right syntax to use near 'freigabe1u1 FROM ada LIMIT 0' at line 1 (42000).",
                        9,
                    ],
                    [
                        "Query error: SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL/MariaDB server version for the right syntax to use near 'freigabe1u1 FROM ada LIMIT 0' at line 1 (42000).",
                        19,
                    ],
                    [
                        "Query error: SQLSTATE[42S22]: Column not found: 1054 Unknown column 'asdsa' in 'where clause' (42S22).",
                        39,
                    ],
                ];
            default: throw new \RuntimeException(sprintf('Unsupported DBA_REFLECTOR %s', $dbaReflector));
        }
    }

    public function testMysqliExecuteQuery(): void
    {
        if (\PHP_VERSION_ID < 80200) {
            self::markTestSkipped('Test requires PHP 8.2.');
        }

        $this->analyse([__DIR__ . '/data/mysqli_execute_query.php'], [
            [
                "Query error: You have an error in your SQL syntax; check the manual that corresponds to your MySQL/MariaDB server version for the right syntax to use near 'freigabe1u1 FROM ada LIMIT 0' at line 1 (1064).",
                11,
            ],
        ]);
    }
}
