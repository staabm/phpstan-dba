<?php

namespace staabm\PHPStanDba\Tests;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use staabm\PHPStanDba\Rules\SyntaxErrorInQueryFunctionRule;

/**
 * @extends RuleTestCase<SyntaxErrorInQueryFunctionRule>
 */
class SyntaxErrorInQueryFunctionRulePdoReflectorTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(SyntaxErrorInQueryFunctionRule::class);
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__.'/../../config/dba.neon',
        ];
    }

    public function testSyntaxErrorInQueryRule(): void
    {
        if (PHP_VERSION_ID < 70400) {
            self::markTestSkipped('Test requires PHP 7.4.');
        }

        if ('pdo-mysql' !== getenv('DBA_REFLECTOR')) {
            $this->markTestSkipped('Only works with PdoMysqlQueryReflector');
        }

        require_once __DIR__.'/data/syntax-error-in-query-function.php';

        $this->analyse([__DIR__.'/data/syntax-error-in-query-function.php'], [
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
        ]);
    }

    public function testSyntaxErrorInPgsqlQueryRule(): void
    {
        if ('pdo-pgsql' !== getenv('DBA_REFLECTOR')) {
            $this->markTestSkipped('Only works with PdoPgsqlQueryReflector');
        }

        require_once __DIR__.'/data/syntax-error-in-query-function.php';

        $this->analyse([__DIR__.'/data/syntax-error-in-query-function.php'], [
            [
                <<<TEXT
Query error: SQLSTATE[42601]: Syntax error: 7 ERROR:  syntax error at or near "freigabe1u1"
LINE 1: SELECT email adaid WHERE gesperrt freigabe1u1 FROM ada LIMIT...
                                          ^ (42601).
TEXT,
                9,
            ],
            [
                <<<TEXT
Query error: SQLSTATE[42601]: Syntax error: 7 ERROR:  syntax error at or near "freigabe1u1"
LINE 1: SELECT email adaid WHERE gesperrt freigabe1u1 FROM ada LIMIT...
                                          ^ (42601).
TEXT,
                19,
            ],
            [
                <<<TEXT
Query error: SQLSTATE[42703]: Undefined column: 7 ERROR:  column "asdsa" does not exist
LINE 1: ...mail, adaid, gesperrt, freigabe1u1 FROM ada WHERE asdsa=1 LI...
                                                             ^ (42703).
TEXT,
                39,
            ],
        ]);
    }
}
