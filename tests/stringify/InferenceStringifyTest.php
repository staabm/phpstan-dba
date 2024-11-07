<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Tests;

use PHPStan\Testing\TypeInferenceTestCase;

class InferenceStringifyTest extends TypeInferenceTestCase
{
    public function dataFileAsserts(): iterable
    {
        yield from $this->gatherAssertTypes(__DIR__ . '/data/stringify.php');

        if (\PHP_VERSION_ID >= 70400 && 'pdo-pgsql' !== getenv('DBA_REFLECTOR')) {
            yield from $this->gatherAssertTypes(__DIR__ . '/data/ast-narrowed-stringify.php');
        }
    }

    /**
     * @dataProvider dataFileAsserts
     *
     * @param mixed ...$args
     */
    public function testFileAsserts(
        string $assertType,
        string $file,
        ...$args
    ): void {
        $this->assertFileAsserts($assertType, $file, ...$args);
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../../config/extensions.neon',
        ];
    }
}
