<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Tests;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\Rules\SyntaxErrorInQueryFunctionRule;
use staabm\PHPStanDba\UnresolvableQueryMixedTypeException;
use staabm\PHPStanDba\UnresolvableQueryStringTypeException;

/**
 * @extends RuleTestCase<SyntaxErrorInQueryFunctionRule>
 */
class UnresolvableQueryFunctionRuleTest extends RuleTestCase
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
        return self::getContainer()->getByType(SyntaxErrorInQueryFunctionRule::class);
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../../config/dba.neon',
        ];
    }

    public function testSyntaxErrorInQueryRule(): void
    {
        $this->analyse([__DIR__ . '/data/unresolvable-query-in-function.php'], [
            [
                'Unresolvable Query: Cannot simulate parameter value for type: mixed.',
                9,
                UnresolvableQueryMixedTypeException::getTip(),
            ],
            [
                'Unresolvable Query: Cannot simulate parameter value for type: mixed.',
                15,
                UnresolvableQueryMixedTypeException::getTip(),
            ],
            [
                'Unresolvable Query: Cannot resolve query with variable type: string.',
                32,
                UnresolvableQueryStringTypeException::getTip(),
            ],
            [
                'Unresolvable Query: Cannot resolve query with variable type: string.',
                37,
                UnresolvableQueryStringTypeException::getTip(),
            ],
        ]);
    }
}
