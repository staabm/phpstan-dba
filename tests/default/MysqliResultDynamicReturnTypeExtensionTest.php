<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Tests;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\LNumber;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\StringType;
use PHPStan\Type\VerbosityLevel;
use PHPUnit\Framework\TestCase;
use staabm\PHPStanDba\Extensions\MysqliResultDynamicReturnTypeExtension;
use staabm\PHPStanDba\MysqliReflection\MysqliResultObjectType;

class MysqliResultDynamicReturnTypeExtensionTest extends TestCase
{
    /**
     * @dataProvider fetchAllModes
     */
    public function testFetchAllMode(?int $mode, ?string $expected): void
    {
        $resultExpr = new Variable('result');
        $modeExpr = $mode === null ? null : new LNumber($mode);
        $scope = $this->createMock(MutatingScope::class);
        $scope->method('getType')->willReturnCallback(static function ($expr) use ($resultExpr): \PHPStan\Type\Type {
            if ($expr === $resultExpr) {
                return MysqliResultObjectType::newWithRowType(new ConstantArrayType(
                    [new ConstantStringType('name')],
                    [new StringType()]
                ));
            }

            if ($expr instanceof LNumber) {
                return new ConstantIntegerType($expr->value);
            }

            throw new \LogicException('Unexpected expression.');
        });
        $method = $this->createMock(MethodReflection::class);
        $method->method('getName')->willReturn('fetch_all');
        $extension = new MysqliResultDynamicReturnTypeExtension($this->createMock(ReflectionProvider::class));
        $call = new MethodCall($resultExpr, 'fetch_all', $modeExpr === null ? [] : [new Arg($modeExpr)]);

        $type = $extension->getTypeFromMethodCall($method, $call, $scope);
        self::assertSame($expected, $type === null ? null : $type->describe(VerbosityLevel::precise()));
    }

    /**
     * @return iterable<string, array{int|null, string|null}>
     */
    public function fetchAllModes(): iterable
    {
        yield 'default' => [null, 'list<array{string}>'];
        yield 'associative' => [1, 'list<array{name: string}>'];
        yield 'numeric' => [2, 'list<array{string}>'];
        yield 'both' => [3, 'list<array{name: string, 0: string}>'];
        yield 'invalid' => [4, null];
    }

    public function testFetchRowUsesNumericMode(): void
    {
        $resultExpr = new Variable('result');
        $scope = $this->createMock(MutatingScope::class);
        $scope->method('getType')->willReturn(MysqliResultObjectType::newWithRowType(new ConstantArrayType(
            [new ConstantStringType('name')],
            [new StringType()]
        )));
        $function = $this->createMock(FunctionReflection::class);
        $function->method('getName')->willReturn('mysqli_fetch_row');
        $extension = new MysqliResultDynamicReturnTypeExtension($this->createMock(ReflectionProvider::class));
        $call = new FuncCall(new Name('mysqli_fetch_row'), [new Arg($resultExpr)]);

        $type = $extension->getTypeFromFunctionCall($function, $call, $scope);
        self::assertNotNull($type);
        self::assertSame('array{string}|false|null', $type->describe(VerbosityLevel::precise()));
    }
}
