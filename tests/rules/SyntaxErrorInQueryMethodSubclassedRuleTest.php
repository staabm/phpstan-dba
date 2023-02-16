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
class SyntaxErrorInQueryMethodSubclassedRuleTest extends RuleTestCase
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

        require_once __DIR__.'/data/syntax-error-in-method-subclassed.php';

        $this->analyse([__DIR__.'/data/syntax-error-in-method-subclassed.php'], $this->getExpectedErrors());
    }

    /** @return list<array{string, int}> */
    public function getExpectedErrors(): array
    {
        $dbaReflector = getenv('DBA_REFLECTOR');

        switch ($dbaReflector) {
            case MysqliQueryReflector::NAME:
                return [
                    [
                        "Query error: You have an error in your SQL syntax; check the manual that corresponds to your MySQL/MariaDB server version for the right syntax to use near 'with syntax error GROUPY by x LIMIT 0' at line 1 (1064).",
                        12,
                    ],
                    [
                        "Query error: You have an error in your SQL syntax; check the manual that corresponds to your MySQL/MariaDB server version for the right syntax to use near 'freigabe1u1 FROM ada LIMIT 0' at line 1 (1064).",
                        18,
                    ],
                ];
            case PdoPgSqlQueryReflector::NAME:
                return [
                    [
                        <<<TEXT
    Query error: SQLSTATE[42601]: Syntax error: 7 ERROR:  syntax error at or near "with"
    LINE 1: SELECT with syntax error GROUPY by x LIMIT 0
                   ^ (42601).
TEXT
                        ,
                        12,
                    ],
                    [
                        <<<TEXT
    Query error: SQLSTATE[42601]: Syntax error: 7 ERROR:  syntax error at or near "freigabe1u1"
    LINE 1: SELECT email adaid WHERE gesperrt freigabe1u1 FROM ada LIMIT...
                                              ^ (42601).
TEXT
                        ,
                        18,
                    ],
                ];
            case PdoMysqlQueryReflector::NAME:
                return [
                    [
                        "Query error: SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL/MariaDB server version for the right syntax to use near 'with syntax error GROUPY by x LIMIT 0' at line 1 (42000).",
                        12,
                    ],
                    [
                        "Query error: SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL/MariaDB server version for the right syntax to use near 'freigabe1u1 FROM ada LIMIT 0' at line 1 (42000).",
                        18,
                    ],
                ];
            default: throw new \RuntimeException(sprintf('Unsupported DBA_REFLECTOR %s', $dbaReflector));
        }
    }
}
