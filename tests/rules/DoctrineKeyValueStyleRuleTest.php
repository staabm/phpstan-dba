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
        return self::getContainer()->getByType(DoctrineKeyValueStyleRule::class);
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../../config/dba.neon',
        ];
    }

    public function testRule(): void
    {
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
                'Query error: Column "ada.adaid" expects value type int, got type mixed',
                56,
            ],
        ];

        $this->analyse([__DIR__ . '/data/doctrine-key-value-style.php'], $expectedErrors);
    }

    public function testLaxIntegerRanges(): void
    {
        $this->analyse([__DIR__ . '/data/doctrine-key-value-style-integer-ranges.php'], []);
    }
}
