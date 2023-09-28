<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Tests;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use staabm\PHPStanDba\Rules\PdoStatementExecuteMethodRule;

/**
 * @extends RuleTestCase<PdoStatementExecuteMethodRule>
 */
class PdoStatementExecuteMethodRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(PdoStatementExecuteMethodRule::class);
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../../config/dba.neon',
        ];
    }

    public function testParameterErrors(): void
    {
        $this->analyse([__DIR__ . '/data/pdo-stmt-execute-error.php'], [
            [
                'Query expects placeholder :adaid, but it is missing from values given.',
                12,
            ],
            [
                'Value :wrongParamName is given, but the query does not contain this placeholder.',
                12,
            ],
            [
                'Query expects placeholder :adaid, but it is missing from values given.',
                15,
            ],
            [
                'Value :wrongParamName is given, but the query does not contain this placeholder.',
                15,
            ],
            /*
            [
                'Query expects placeholder :adaid, but it is missing from values given.',
                18,
            ],
            [
                'Value :wrongParamValue is given, but the query does not contain this placeholder.',
                18,
            ],
            */
            [
                'Query expects placeholder :adaid, but it is missing from values given.',
                21,
            ],
            [
                'Query expects 2 placeholders, but 1 value is given.',
                24,
            ],
            [
                'Query expects placeholder :email, but it is missing from values given.',
                27,
            ],
            [
                'Query expects placeholder :adaid, but it is missing from values given.',
                30,
            ],
            [
                'Query expects placeholder :adaid, but it is missing from values given.',
                36,
            ],
            [
                'Value :wrongParamName is given, but the query does not contain this placeholder.',
                36,
            ],
            [
                'Value :email is given, but the query does not contain this placeholder.',
                38,
            ],
            [
                'Query expects placeholder :asdsa, but it is missing from values given.',
                54,
            ],
            [
                'Query expects placeholder :adaid, but it is missing from values given.',
                61,
            ],
            [
                'Value :wrongParamName is given, but the query does not contain this placeholder.',
                61,
            ],
            [
                'Query expects placeholder :adaid, but it is missing from values given.',
                65,
            ],
            [
                'Value :wrongParamName is given, but the query does not contain this placeholder.',
                65,
            ],
            [
                'Query expects placeholder :email, but it is missing from values given.',
                69,
            ],
            [
                'Query expects placeholder :adaid, but it is missing from values given.',
                73,
            ],
        ]);
    }

    public function testNamedPlaceholderBug(): void
    {
        $this->analyse([__DIR__ . '/data/named-placeholder-bug.php'], []);
    }

    public function testPlaceholderBug(): void
    {
        $this->analyse([__DIR__ . '/data/placeholder-bug.php'], []);
    }
}
