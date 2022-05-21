<?php

namespace staabm\PHPStanDba\Tests;

use PHPStan\Rules\Rule;
use staabm\PHPStanDba\Rules\SyntaxErrorInPreparedStatementMethodRule;
use staabm\PHPStanDba\Rules\SyntaxErrorInQueryFunctionRule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<SyntaxErrorInQueryFunctionRule>
 */
class SyntaxErrorInQueryFunctionRuleMysqliReflectorTest extends RuleTestCase
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
        if ('mysqli' !== getenv('DBA_REFLECTOR')) {
            $this->markTestSkipped('Only works with MysqliReflector');
        }

        require_once __DIR__.'/data/syntax-error-in-query-function.php';

        $this->analyse([__DIR__.'/data/syntax-error-in-query-function.php'], [
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
        ]);
    }
}
