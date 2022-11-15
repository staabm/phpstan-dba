<?php

namespace staabm\PHPStanDba\Tests;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use staabm\PHPStanDba\QueryReflection\DibiQueryReflector;
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

        if (DibiQueryReflector::NAME === getenv('DBA_REFLECTOR')) {
            $expectedErrors = [
                [
                    "Query error: You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near 'FROM ada' at line 1 (1064).",
                    13,
                ],
                [
                    "Query error: You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near 'FROM ada' at line 1 (1064).",
                    19,
                ],
                [
                    "Query error: You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near 'FROM ada' at line 1 (1064).",
                    25,
                ],
                [
                    'fetchSingle requires exactly 1 selected column, got 2.',
                    26,
                ],
                [
                    "Query error: You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near 'FROM ada' at line 1 (1064).",
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
                    46,
                ],
            ];
        } else {
            throw new \RuntimeException('Unsupported DBA_REFLECTOR '.getenv('DBA_REFLECTOR'));
        }

        require_once __DIR__.'/data/syntax-error-in-dibi-prepared-statement.php';
        $this->analyse([__DIR__.'/data/syntax-error-in-dibi-prepared-statement.php'], $expectedErrors);
    }
}
