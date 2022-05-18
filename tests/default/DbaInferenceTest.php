<?php

namespace staabm\PHPStanDba\Tests;

use Composer\InstalledVersions;
use Composer\Semver\VersionParser;
use function getenv;
use PHPStan\Testing\TypeInferenceTestCase;

class DbaInferenceTest extends TypeInferenceTestCase
{
    public function dataFileAsserts(): iterable
    {
        yield from $this->gatherAssertTypes(__DIR__.'/data/doctrine-dbal.php');

        // make sure class constants can be resolved
        require_once __DIR__.'/data/pdo.php';
        yield from $this->gatherAssertTypes(__DIR__.'/data/pdo.php');

        if ('pdo-pgsql' === getenv('DBA_REFLECTOR')) {
            // TODO
        } else {
            yield from $this->gatherAssertTypes(__DIR__.'/data/pdo-mysql.php');
        }

        // make sure class constants can be resolved
        require_once __DIR__.'/data/pdo-quote.php';
        yield from $this->gatherAssertTypes(__DIR__.'/data/pdo-quote.php');

        // make sure class constants can be resolved
        require_once __DIR__.'/data/pdo-prepare.php';
        yield from $this->gatherAssertTypes(__DIR__.'/data/pdo-prepare.php');

        // make sure class constants can be resolved
        require_once __DIR__.'/data/pdo-stmt-fetch.php';
        yield from $this->gatherAssertTypes(__DIR__.'/data/pdo-stmt-fetch.php');

        // make sure class constants can be resolved
        require_once __DIR__.'/data/pdo-fetch-types.php';
        yield from $this->gatherAssertTypes(__DIR__.'/data/pdo-fetch-types.php');

        yield from $this->gatherAssertTypes(__DIR__.'/data/pdo-column-count.php');
        yield from $this->gatherAssertTypes(__DIR__.'/data/pdo-stmt-execute.php');

        yield from $this->gatherAssertTypes(__DIR__.'/data/mysqli.php');
        yield from $this->gatherAssertTypes(__DIR__.'/data/mysqli-escape.php');

        // make sure class definitions within the test files are known to reflection
        require_once __DIR__.'/data/runMysqlQuery.php';
        yield from $this->gatherAssertTypes(__DIR__.'/data/runMysqlQuery.php');

        // XXX cases which are not yet supported by the PdoQueryReflector
        if ('pdo' !== getenv('DBA_REFLECTOR') && 'pdo-pgsql' !== getenv('DBA_REFLECTOR')) {
            yield from $this->gatherAssertTypes(__DIR__.'/data/query-alias.php');
        }

        yield from $this->gatherAssertTypes(__DIR__.'/data/bug254.php');

        if (InstalledVersions::satisfies(new VersionParser(), 'phpstan/phpstan', '^1.4.7')) {
            yield from $this->gatherAssertTypes(__DIR__.'/data/pdo-stmt-set-fetch-mode.php');
        }

        yield from $this->gatherAssertTypes(__DIR__.'/data/pdo-union-result.php');
        yield from $this->gatherAssertTypes(__DIR__.'/data/mysqli-union-result.php');
    }

    /**
     * @dataProvider dataFileAsserts
     *
     * @param mixed ...$args
     */
    public function testFileAsserts(
        string $assertType,
        string $file,
               ...$args,
    ): void {
        $this->assertFileAsserts($assertType, $file, ...$args);
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__.'/../../config/stubFiles.neon',
            __DIR__.'/../../config/extensions.neon',
        ];
    }
}
