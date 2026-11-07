<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Tests;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar\LNumber;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\FunctionVariant;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\VerbosityLevel;
use PHPUnit\Framework\TestCase;
use staabm\PHPStanDba\Extensions\PdoQuoteDynamicReturnTypeExtension;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\QueryReflection\RuntimeConfiguration;

class PdoQuoteDynamicReturnTypeExtensionTest extends TestCase
{
    /**
     * @dataProvider parameterTypes
     * @runInSeparateProcess
     *
     * @param RuntimeConfiguration::ERROR_MODE* $errorMode
     */
    public function testParameterType(?int $parameterType, int $phpVersion, string $errorMode, string $expected): void
    {
        QueryReflection::getRuntimeConfiguration()->errorMode($errorMode);

        $scope = $this->createMock(MutatingScope::class);
        $scope->method('getType')->willReturnCallback(static function ($expr): Type {
            if ($expr instanceof LNumber) {
                return new ConstantIntegerType($expr->value);
            }

            return new StringType();
        });

        $method = $this->createMock(MethodReflection::class);
        $method->method('getVariants')->willReturn([
            new FunctionVariant(TemplateTypeMap::createEmpty(), null, [], false, TypeCombinator::union(new StringType(), new ConstantBooleanType(false))),
        ]);
        $args = [new Arg(new Variable('value'))];
        if ($parameterType !== null) {
            $args[] = new Arg(new LNumber($parameterType));
        }

        $extension = new PdoQuoteDynamicReturnTypeExtension(new PhpVersion($phpVersion));
        $type = $extension->getTypeFromMethodCall($method, new MethodCall(new Variable('pdo'), 'quote', $args), $scope);
        self::assertSame($expected, $type->describe(VerbosityLevel::precise()));
    }

    /**
     * @return iterable<string, array{int|null, int, RuntimeConfiguration::ERROR_MODE*, string}>
     */
    public function parameterTypes(): iterable
    {
        yield 'default string' => [null, 70400, RuntimeConfiguration::ERROR_MODE_BOOL, 'string'];
        yield 'integer' => [1, 70400, RuntimeConfiguration::ERROR_MODE_BOOL, 'string'];
        yield 'string' => [2, 70400, RuntimeConfiguration::ERROR_MODE_BOOL, 'string'];
        yield 'boolean' => [5, 70400, RuntimeConfiguration::ERROR_MODE_BOOL, 'string'];
        yield 'LOB' => [3, 70400, RuntimeConfiguration::ERROR_MODE_BOOL, 'string|false'];
        yield 'unsupported' => [42, 70400, RuntimeConfiguration::ERROR_MODE_BOOL, 'string|false'];
        yield 'PHP 7 default errors' => [3, 70400, RuntimeConfiguration::ERROR_MODE_DEFAULT, 'string|false'];
        yield 'PHP 8 default errors' => [3, 80000, RuntimeConfiguration::ERROR_MODE_DEFAULT, 'string'];
        yield 'explicit exceptions' => [3, 70400, RuntimeConfiguration::ERROR_MODE_EXCEPTION, 'string'];
    }
}
