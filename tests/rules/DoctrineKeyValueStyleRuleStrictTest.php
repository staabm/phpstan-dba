<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Tests;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\QueryReflection\RuntimeConfiguration;
use staabm\PHPStanDba\Rules\DoctrineKeyValueStyleRule;

/**
 * @extends RuleTestCase<DoctrineKeyValueStyleRule>
 */
class DoctrineKeyValueStyleRuleStrictTest extends RuleTestCase
{
    protected function setUp(): void
    {
        QueryReflection::getRuntimeConfiguration()->parameterTypeValidation(RuntimeConfiguration::VALIDATION_MODE_STRICT);
    }

    protected function tearDown(): void
    {
        QueryReflection::getRuntimeConfiguration()->parameterTypeValidation(RuntimeConfiguration::VALIDATION_MODE_LAX);
    }

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

    public function testStrictIntegerRanges(): void
    {
        $expectedErrors = [
            [
                'Query error: Column "ada.adaid" expects value type int<-32768, 32767>, got type int',
                10,
            ],
            [
                'Query error: Column "ada.adaid" expects value type int<-32768, 32767>, got type int<0, 65535>',
                18,
            ],
        ];

        $this->analyse([__DIR__ . '/data/doctrine-key-value-style-integer-ranges.php'], $expectedErrors);
    }
}
