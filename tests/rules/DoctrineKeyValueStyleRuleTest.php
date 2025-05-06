<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Tests;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use staabm\PHPStanDba\Rules\DoctrineKeyValueStyleRule;

/**
 * @extends RuleTestCase<DoctrineKeyValueStyleRule>
 */
class DoctrineKeyValueStyleRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        $rule = self::getContainer()->getByType(DoctrineKeyValueStyleRule::class);
        $rule->classMethods[] = ['staabm\PHPStanDba\Tests\Fixture\Connection', 'assembleNoArrays', []];
        $rule->classMethods[] = ['staabm\PHPStanDba\Tests\Fixture\Connection', 'assembleOneArray', [1]];
        $rule->classMethods[] = ['staabm\PHPStanDba\Tests\Fixture\Connection', 'assembleTwoArrays', [1, 2]];
        return $rule;
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../../config/dba.neon',
        ];
    }

    public function testRule(): void
    {
        $err56 = 'Query error: Column "ada.adaid" expects value type int, got type mixed';
        if (PHP_VERSION_ID < 80000) {
            // PHP 7.x does not support native mixed type
            $err56 = 'Query error: Column "ada.adaid" expects value type int, got type DoctrineKeyValueStyleRuleTest\mixed';
        }

        $expectedErrors = [
            [
                'Argument #0 expects a constant string, got string',
                11,
            ],
            [
                'Query error: Table "not_a_table" does not exist',
                16,
            ],
            [
                'Query error: Argument #1 is not a constant array, got \'not_an_array\'',
                21,
            ],
            [
                'Query error: Argument #2 is not a constant array, got \'not_an_array\'',
                26,
            ],
            [
                'Query error: Argument #1 is not a constant array, got non-empty-array<string, \'foo\'>',
                31,
            ],
            [
                'Query error: Element #0 of argument #1 must have a string key, got 42',
                36,
            ],
            [
                'Query error: Column "ada.not_a_column" does not exist',
                41,
            ],
            [
                'Query error: Column "ada.adaid" expects value type int, got type string',
                46,
            ],
            [
                'Query error: Column "ada.adaid" expects value type int, got type int|null',
                51,
            ],
            [
                $err56,
                56,
            ],
            [
                'Query error: Table "not_a_table1" does not exist',
                112,
            ],
            [
                'Query error: Table "not_a_table2" does not exist',
                112,
            ],
            [
                'Query error: Column "ada.not_a_column1" does not exist',
                120,
            ],
            [
                'Query error: Column "ada.not_a_column2" does not exist',
                120,
            ],
            [
                'Query error: Column "typemix.c_int" expects value type int, got type float|int',
                128,
            ],
            [
                'Query error: Column "typemix.c_int" expects value type int, got type (int|string)',
                136,
            ],
        ];

        $this->analyse([__DIR__ . '/data/doctrine-key-value-style.php'], $expectedErrors);
    }

    public function testLaxIntegerRanges(): void
    {
        $this->analyse([__DIR__ . '/data/doctrine-key-value-style-integer-ranges.php'], []);
    }
}
