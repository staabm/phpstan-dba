<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Tests;

use PHPStan\Testing\TypeInferenceTestCase;
use function getenv;

class SqlAstInferenceTest extends TypeInferenceTestCase
{
    public function dataFileAsserts(): iterable
    {
        if (\PHP_VERSION_ID >= 70400 && 'pdo-pgsql' !== getenv('DBA_REFLECTOR')) {
            yield from $this->gatherAssertTypes(__DIR__ . '/data/sql-ast-narrowing.php');
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
            __DIR__ . '/../../config/stubFiles.neon',
            __DIR__ . '/../../config/extensions.neon',
        ];
    }
}
