<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Tests;

use PDO;
use staabm\PHPStanDba\DbSchema\SchemaHasherMysql;
use staabm\PHPStanDba\QueryReflection\MysqliQueryReflector;
use staabm\PHPStanDba\QueryReflection\PdoMysqlQueryReflector;
use staabm\PHPStanDba\QueryReflection\PdoPgSqlQueryReflector;
use staabm\PHPStanDba\QueryReflection\QueryReflector;
use staabm\PHPStanDba\QueryReflection\RecordingQueryReflector;
use staabm\PHPStanDba\QueryReflection\ReflectionCache;
use staabm\PHPStanDba\QueryReflection\ReplayAndRecordingQueryReflector;
use staabm\PHPStanDba\QueryReflection\ReplayQueryReflector;

final class ReflectorFactory
{
    public const MODE_RECORDING = 'recording';

    public const MODE_REPLAY = 'replay';

    public const MODE_REPLAY_AND_RECORDING = 'replay-and-recording';

    public static function create(string $cacheDir): QueryReflector
    {
        // handle defaults
        if (false !== getenv('GITHUB_ACTION')) {
            $host = getenv('DBA_HOST') ?: '127.0.0.1';
            $user = getenv('DBA_USER') ?: 'root';
            $password = getenv('DBA_PASSWORD') ?: 'root';
            $dbname = getenv('DBA_DATABASE') ?: 'phpstan_dba';
            $ssl = false;
            $mode = getenv('DBA_MODE') ?: self::MODE_RECORDING;
            $reflector = getenv('DBA_REFLECTOR') ?: 'mysqli';
            $platform = getenv('DBA_PLATFORM') ?: 'mysql';
        } else {
            $host = getenv('DBA_HOST') ?: $_ENV['DBA_HOST'];
            $user = getenv('DBA_USER') ?: $_ENV['DBA_USER'];
            $password = getenv('DBA_PASSWORD') ?: $_ENV['DBA_PASSWORD'];
            $dbname = getenv('DBA_DATABASE') ?: $_ENV['DBA_DATABASE'];
            $ssl = (string) (getenv('DBA_SSL') ?: $_ENV['DBA_SSL'] ?? '');
            $mode = getenv('DBA_MODE') ?: $_ENV['DBA_MODE'];
            $reflector = getenv('DBA_REFLECTOR') ?: $_ENV['DBA_REFLECTOR'];
            $platform = getenv('DBA_PLATFORM') ?: $_ENV['DBA_PLATFORM'] ?? '';
        }

        // make env vars available to tests, in case non are defined yet
        $_ENV['DBA_REFLECTOR'] = $reflector;
        $_ENV['DBA_PLATFORM'] = $platform;

        // we need to record the reflection information in both, phpunit and phpstan since we are replaying it in both CI jobs.
        // in a regular application you will use phpstan-dba only within your phpstan CI job, therefore you only need 1 cache-file.
        // phpstan-dba itself is a special case, since we are testing phpstan-dba with phpstan-dba.
        $cacheFile = sprintf(
            '%s/.phpunit-phpstan-dba-%s.cache',
            $cacheDir,
            $reflector
        );
        if (\defined('__PHPSTAN_RUNNING__')) {
            $cacheFile = $cacheDir . '/.phpstan-dba-' . $reflector . '.cache';
        }

        if (str_starts_with($mode, 'empty-')) {
            file_put_contents($cacheFile, ''); // clear cache
            $mode = substr($mode, 6);
        }

        $reflectionCache = ReflectionCache::create(
            $cacheFile
        );

        if (self::MODE_RECORDING === $mode || self::MODE_REPLAY_AND_RECORDING === $mode) {
            $schemaHasher = null;

            $port = null;
            if (str_contains($host, ':')) {
                [$host, $port] = explode(':', $host, 2);
                $port = (int) $port;
            }

            if ('mysqli' === $reflector) {
                $mysqli = mysqli_init();
                if (! $mysqli) {
                    throw new \RuntimeException('Unable to init mysqli');
                }
                $mysqli->real_connect($host, $user, $password, $dbname, $port, null, $ssl ? MYSQLI_CLIENT_SSL : 0);
                $reflector = new MysqliQueryReflector($mysqli);
                $schemaHasher = new SchemaHasherMysql($mysqli);
            } elseif ('pdo-mysql' === $reflector) {
                $options = [];
                if ($ssl !== '') {
                    $options[PDO::MYSQL_ATTR_SSL_CA] = $ssl;
                    $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
                }
                $port = $port !== null ? ';port=' . $port : '';
                $pdo = new PDO(sprintf('mysql:dbname=%s;host=%s', $dbname, $host) . $port, $user, $password, $options);
                $reflector = new PdoMysqlQueryReflector($pdo);
                $schemaHasher = new SchemaHasherMysql($pdo);
            } elseif ('pdo-pgsql' === $reflector) {
                $port = $port !== null ? ';port=' . $port : '';
                $pdo = new PDO(sprintf('pgsql:dbname=%s;host=%s', $dbname, $host) . $port, $user, $password);
                $reflector = new PdoPgSqlQueryReflector($pdo);
            } else {
                throw new \RuntimeException('Unknown reflector: ' . $reflector);
            }

            if (self::MODE_REPLAY_AND_RECORDING === $mode) {
                if (null === $schemaHasher) {
                    throw new \RuntimeException('Schema hasher required.');
                }

                $reflector = new ReplayAndRecordingQueryReflector(
                    $reflectionCache,
                    $reflector,
                    $schemaHasher
                );
            } else {
                $reflector = new RecordingQueryReflector(
                    $reflectionCache,
                    $reflector
                );
            }
        } elseif (self::MODE_REPLAY === $mode) {
            $reflector = new ReplayQueryReflector(
                $reflectionCache
            );
        } else {
            throw new \RuntimeException('Unknown mode: ' . $mode);
        }

        return $reflector;
    }
}
