<?php

namespace staabm\PHPStanDba\Tests;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use staabm\PHPStanDba\Rules\SyntaxErrorInQueryMethodRule;

/**
 * @extends RuleTestCase<SyntaxErrorInQueryMethodRule>
 */
class SyntaxErrorInQueryMethodSubclassedRulePdoReflectorTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(SyntaxErrorInQueryMethodRule::class);
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__.'/config/subclassed-method-rule.neon',
        ];
    }

    public function testSyntaxErrorInQueryRule(): void
    {
        if (\PHP_VERSION_ID < 70400) {
            self::markTestSkipped('Test requires PHP 7.4.');
        }

        if ('pdo-mysql' !== getenv('DBA_REFLECTOR')) {
            $this->markTestSkipped('Only works with PdoMysqlQueryReflector');
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
        ]);
    }

    public function testSyntaxErrorInPgsqlQueryRule(): void
    {
        if ('pdo-pgsql' !== getenv('DBA_REFLECTOR')) {
            $this->markTestSkipped('Only works with PdoPgsqlQueryReflector');
        }

        require_once __DIR__.'/data/syntax-error-in-method-subclassed.php';

        $this->analyse([__DIR__.'/data/syntax-error-in-method-subclassed.php'], [
            [
                <<<TEXT
Query error: SQLSTATE[42601]: Syntax error: 7 ERROR:  syntax error at or near "with"
LINE 1: SELECT with syntax error GROUPY by x LIMIT 0
               ^ (42601).
TEXT,
                12,
            ],
            [
                <<<TEXT
Query error: SQLSTATE[42601]: Syntax error: 7 ERROR:  syntax error at or near "freigabe1u1"
LINE 1: SELECT email adaid WHERE gesperrt freigabe1u1 FROM ada LIMIT...
                                          ^ (42601).
TEXT,
                18,
            ],
        ]);
    }
}
