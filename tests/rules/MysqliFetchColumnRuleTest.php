<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Tests;

use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\Rules\MysqliFetchColumnRule;

/**
 * @extends RuleTestCase<MysqliFetchColumnRule>
 */
class MysqliFetchColumnRuleTest extends RuleTestCase
{
    private bool $reportMaybes = false;

    protected function setUp(): void
    {
        parent::setUp();

        QueryReflection::getRuntimeConfiguration()->debugMode(true);
    }

    protected function tearDown(): void
    {
        QueryReflection::getRuntimeConfiguration()->debugMode(false);
    }

    protected function getRule(): Rule
    {
        return new MysqliFetchColumnRule(
            self::getContainer()->getByType(ReflectionProvider::class),
            $this->reportMaybes
        );
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../../config/dba.neon',
        ];
    }

    public function testFetchColumnRule(): void
    {
        if ('mysqli' !== getenv('DBA_REFLECTOR')) {
            self::markTestSkipped('Test requires mysqli');
        }
        if (PHP_VERSION_ID < 80100) {
            self::markTestSkipped('Test requires PHP 8.1');
        }
        $this->reportMaybes = true;
        $this->analyse([__DIR__ . '/data/mysqli-fetch-column.php'], [
            [
                'Column offset 999 is out of range; valid offsets are 0|1|2.',
                21,
            ],
            [
                'Column offset 0|999 may be out of range; valid offsets are 0|1|2.',
                24,
            ],
            [
                'Column offset int<0, 8> may be out of range; valid offsets are 0|1|2.',
                28,
            ],
            [
                'Column offset 999 is out of range; valid offsets are 0|1|2.',
                43,
            ],
            [
                'Column offset 0|999 may be out of range; valid offsets are 0|1|2.',
                46,
            ],
            [
                'Column offset int<0, 8> may be out of range; valid offsets are 0|1|2.',
                50,
            ],
        ]);
    }

    public function testFetchColumnRuleNoMaybes(): void
    {
        if ('mysqli' !== getenv('DBA_REFLECTOR')) {
            self::markTestSkipped('Test requires mysqli');
        }
        if (PHP_VERSION_ID < 80100) {
            self::markTestSkipped('Test requires PHP 8.1');
        }
        $this->reportMaybes = false;
        $this->analyse([__DIR__ . '/data/mysqli-fetch-column.php'], [
            [
                'Column offset 999 is out of range; valid offsets are 0|1|2.',
                21,
            ],
            [
                'Column offset 999 is out of range; valid offsets are 0|1|2.',
                43,
            ],
        ]);
    }
}
