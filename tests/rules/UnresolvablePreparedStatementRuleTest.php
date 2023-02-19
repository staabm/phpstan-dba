<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Tests;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\Rules\SyntaxErrorInPreparedStatementMethodRule;
use staabm\PHPStanDba\UnresolvableQueryMixedTypeException;
use staabm\PHPStanDba\UnresolvableQueryStringTypeException;

/**
 * @extends RuleTestCase<SyntaxErrorInPreparedStatementMethodRule>
 */
class UnresolvablePreparedStatementRuleTest extends RuleTestCase
{
    protected function setUp(): void
    {
        QueryReflection::getRuntimeConfiguration()->debugMode(true);
    }

    protected function tearDown(): void
    {
        QueryReflection::getRuntimeConfiguration()->debugMode(false);
    }

    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(SyntaxErrorInPreparedStatementMethodRule::class);
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../../config/dba.neon',
        ];
    }

    public function testSyntaxErrorInQueryRule(): void
    {
        require_once __DIR__ . '/data/unresolvable-statement.php';

        $this->analyse([__DIR__ . '/data/unresolvable-statement.php'], [
            [
                'Unresolvable Query: Cannot simulate parameter value for type: mixed.',
                11,
                UnresolvableQueryMixedTypeException::getTip(),
            ],
            [
                'Unresolvable Query: Cannot resolve query with variable type: string.',
                30,
                UnresolvableQueryStringTypeException::getTip(),
            ],
        ]);
    }
}
