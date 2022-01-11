<?php

namespace staabm\PHPStanDba\Tests;

use PHPStan\Rules\Rule;
use staabm\PHPStanDba\Rules\PdoStatementExecuteErrorMethodRule;
use Symplify\PHPStanExtensions\Testing\AbstractServiceAwareRuleTestCase;

/**
 * @extends AbstractServiceAwareRuleTestCase<PdoStatementExecuteErrorMethodRule>
 */
class PdoStatementExecuteErrorMethodRuleTest extends AbstractServiceAwareRuleTestCase
{
    protected function getRule(): Rule
    {
        return $this->getRuleFromConfig(PdoStatementExecuteErrorMethodRule::class, __DIR__.'/../config/dba.neon');
    }

    public function testParameterErrors(): void
    {
        require_once __DIR__.'/data/pdo-stmt-execute-error.php';

        $this->analyse([__DIR__.'/data/pdo-stmt-execute-error.php'], [
            [
                'Query expects placeholder :adaid, but it is missing from values given to execute().',
                12,
            ],
            [
                'Value :wrongParamName is given to execute(), but the query does not contain this placeholder.',
                12,
            ],
            [
                'Query expects placeholder :adaid, but it is missing from values given to execute().',
                15,
            ],
            [
                'Value :wrongParamName is given to execute(), but the query does not contain this placeholder.',
                15,
            ],
            [
                'Query expects placeholder :adaid, but it is missing from values given to execute().',
                18,
            ],
            [
                'Value :wrongParamValue is given to execute(), but the query does not contain this placeholder.',
                18,
            ],
            [
                'Query expects 1 placeholder, but no values are given to execute().',
                21,
            ],
            [
                'Query expects 2 placeholders, but 1 value is given to execute().',
                24,
            ],
            [
                'Query expects 2 placeholders, but 1 value is given to execute().',
                27,
            ],
            [
                'Query expects 2 placeholders, but 1 value is given to execute().',
                30,
            ],
            [
                'Query expects placeholder :adaid, but it is missing from values given to execute().',
                36,
            ],
            [
                'Value :wrongParamName is given to execute(), but the query does not contain this placeholder.',
                36,
            ],
            [
                'Query expects 1 placeholder, but 2 values are given to execute().',
                38,
            ],
        ]);
    }
}
