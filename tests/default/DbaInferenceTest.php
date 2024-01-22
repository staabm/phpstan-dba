<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Tests;

use Composer\InstalledVersions;
use Composer\Semver\VersionParser;
use PHPStan\Testing\TypeInferenceTestCase;
use function getenv;

class DbaInferenceTest extends TypeInferenceTestCase
{
    public function dataFileAsserts(): iterable
    {
        if (\PHP_VERSION_ID >= 70300) {
            if (! InstalledVersions::isInstalled('doctrine/dbal')) {
                throw new \Exception('doctrine/dbal 3.x is required to run tests for php 7.3+. Please install it via composer.');
            }

            yield from $this->gatherAssertTypes(__DIR__ . '/data/doctrine-dbal.php');
            yield from $this->gatherAssertTypes(__DIR__ . '/data/inference-placeholder.php');
        }

        // make sure class constants can be resolved
        yield from $this->gatherAssertTypes(__DIR__ . '/data/pdo.php');

        if ('pdo-pgsql' === getenv('DBA_REFLECTOR')) {
            yield from $this->gatherAssertTypes(__DIR__ . '/data/pdo-pgsql.php');
            yield from $this->gatherAssertTypes(__DIR__ . '/data/typemix-pgsql.php');
        } else {
            yield from $this->gatherAssertTypes(__DIR__ . '/data/pdo-mysql.php');
            yield from $this->gatherAssertTypes(__DIR__ . '/data/typemix-mysql.php');
        }

        // DbaApi related tests cannot be replayed, because cache file keys do not reflect the used DbaApi
        if (ReflectorFactory::MODE_RECORDING === getenv('DBA_MODE')) {
            yield from $this->gatherAssertTypes(__DIR__ . '/data/dibi.php');
        }

        yield from $this->gatherAssertTypes(__DIR__ . '/data/pdo-quote.php');
        yield from $this->gatherAssertTypes(__DIR__ . '/data/pdo-prepare.php');
        yield from $this->gatherAssertTypes(__DIR__ . '/data/pdo-stmt-fetch.php');
        yield from $this->gatherAssertTypes(__DIR__ . '/data/pdo-fetch-types.php');
        yield from $this->gatherAssertTypes(__DIR__ . '/data/pdo-column-count.php');
        yield from $this->gatherAssertTypes(__DIR__ . '/data/pdo-stmt-execute.php');

        // XXX skip mysqli tests for now
        // yield from $this->gatherAssertTypes(__DIR__ . '/data/mysqli.php');
        // yield from $this->gatherAssertTypes(__DIR__ . '/data/mysqli-union-result.php');

        yield from $this->gatherAssertTypes(__DIR__ . '/data/mysqli-escape.php');

        yield from $this->gatherAssertTypes(__DIR__ . '/data/runMysqlQuery.php');

        // TODO pgsql: doesn't resolve null?
        if ('pdo-pgsql' !== getenv('DBA_REFLECTOR')) {
            yield from $this->gatherAssertTypes(__DIR__ . '/data/query-alias.php');
        }

        yield from $this->gatherAssertTypes(__DIR__ . '/data/bug254.php');

        if (InstalledVersions::satisfies(new VersionParser(), 'phpstan/phpstan', '^1.4.7')) {
            yield from $this->gatherAssertTypes(__DIR__ . '/data/pdo-stmt-set-fetch-mode.php');
        }

        yield from $this->gatherAssertTypes(__DIR__ . '/data/pdo-union-result.php');
        yield from $this->gatherAssertTypes(__DIR__ . '/data/pdo-default-fetch-types.php');
        yield from $this->gatherAssertTypes(__DIR__ . '/data/bug372.php');
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
            'phar://phpstan.phar/conf/bleedingEdge.neon',
            __DIR__ . '/../../config/stubFiles.neon',
            __DIR__ . '/../../config/extensions.neon',
        ];
    }
}
