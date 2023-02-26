<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Tests;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\Rules\SyntaxErrorInQueryMethodRule;
use staabm\PHPStanDba\UnresolvableQueryMixedTypeException;
use staabm\PHPStanDba\UnresolvableQueryStringTypeException;

/**
 * @extends RuleTestCase<SyntaxErrorInQueryMethodRule>
 */
class UnresolvableQueryMethodRuleTest extends RuleTestCase
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
        return self::getContainer()->getByType(SyntaxErrorInQueryMethodRule::class);
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../../config/dba.neon',
        ];
    }

    public function testSyntaxErrorInQueryRule(): void
    {
        $this->analyse([__DIR__ . '/data/unresolvable-query-in-method.php'], [
            [
                'Unresolvable Query: Cannot simulate parameter value for type: mixed.',
                11,
                UnresolvableQueryMixedTypeException::getTip(),
            ],
            [
                'Unresolvable Query: Cannot simulate parameter value for type: mixed.',
                17,
                UnresolvableQueryMixedTypeException::getTip(),
            ],
            [
                'Unresolvable Query: Cannot resolve query with variable type: string.',
                34,
                UnresolvableQueryStringTypeException::getTip(),
            ],
            [
                'Unresolvable Query: Cannot resolve query with variable type: string.',
                39,
                UnresolvableQueryStringTypeException::getTip(),
            ],
        ]);
    }

    public function testBug536(): void
    {
        $this->analyse([__DIR__ . '/data/bug-536.php'], []);
    }

    public function testBug548(): void
    {
        $this->analyse([__DIR__ . '/data/bug-548.php'], []);
    }

    public function testBug547(): void
    {
        $this->analyse([__DIR__ . '/data/bug-547.php'], []);
    }
}
