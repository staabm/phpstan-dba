<?php

namespace staabm\PHPStanDba\Tests;

use PHPStan\Rules\Rule;
use staabm\PHPStanDba\Rules\PdoStatementExecuteMethodRule;
use Symplify\PHPStanExtensions\Testing\AbstractServiceAwareRuleTestCase;

/**
 * @extends AbstractServiceAwareRuleTestCase<PdoStatementExecuteMethodRule>
 */
class PdoStatementExecuteMethodRuleTest extends AbstractServiceAwareRuleTestCase
{
    protected function getRule(): Rule
    {
        return $this->getRuleFromConfig(PdoStatementExecuteMethodRule::class, __DIR__.'/../config/dba.neon');
    }

    public function testParameterErrors(): void
    {
        require_once __DIR__.'/data/pdo-stmt-execute-error.php';

        $this->analyse([__DIR__.'/data/pdo-stmt-execute-error.php'], [
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
                'Value :adaid is given, but the query does not contain this placeholder.',
                54,
            ],
        ]);
    }
}
