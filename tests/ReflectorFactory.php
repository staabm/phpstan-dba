<?php

namespace staabm\PHPStanDba\Tests;

use mysqli;
use PDO;
use staabm\PHPStanDba\DbSchema\SchemaHasherMysql;
use staabm\PHPStanDba\QueryReflection\MysqliQueryReflector;
use staabm\PHPStanDba\QueryReflection\PdoPgSqlQueryReflector;
use staabm\PHPStanDba\QueryReflection\PdoMysqlQueryReflector;
use staabm\PHPStanDba\QueryReflection\QueryReflector;
use staabm\PHPStanDba\QueryReflection\RecordingQueryReflector;
use staabm\PHPStanDba\QueryReflection\ReflectionCache;
use staabm\PHPStanDba\QueryReflection\ReplayAndRecordingQueryReflector;
use staabm\PHPStanDba\QueryReflection\ReplayQueryReflector;

final class ReflectorFactory
{
    public static function create(string $cacheDir): QueryReflector
    {
        // handle defaults
        if (false !== getenv('GITHUB_ACTION')) {
            $host = getenv('DBA_HOST') ?: '127.0.0.1';
            $user = getenv('DBA_USER') ?: 'root';
            $password = getenv('DBA_PASSWORD') ?: 'root';
            $dbname = getenv('DBA_DATABASE') ?: 'phpstan_dba';
            $mode = getenv('DBA_MODE') ?: 'recording';
            $reflector = getenv('DBA_REFLECTOR') ?: 'mysqli';
        } else {
            $host = getenv('DBA_HOST') ?: $_ENV['DBA_HOST'];
            $user = getenv('DBA_USER') ?: $_ENV['DBA_USER'];
            $password = getenv('DBA_PASSWORD') ?: $_ENV['DBA_PASSWORD'];
            $dbname = getenv('DBA_DATABASE') ?: $_ENV['DBA_DATABASE'];
            $mode = getenv('DBA_MODE') ?: $_ENV['DBA_MODE'];
            $reflector = getenv('DBA_REFLECTOR') ?: $_ENV['DBA_REFLECTOR'];
        }

        // make env vars available to tests, in case non are defined yet
        $_ENV['DBA_REFLECTOR'] = $reflector;

        // we need to record the reflection information in both, phpunit and phpstan since we are replaying it in both CI jobs.
        // in a regular application you will use phpstan-dba only within your phpstan CI job, therefore you only need 1 cache-file.
        // phpstan-dba itself is a special case, since we are testing phpstan-dba with phpstan-dba.
        $cacheFile = sprintf(
            '%s/.phpunit-phpstan-dba-%s.cache',
            $cacheDir,
            $reflector,
        );
        if (\defined('__PHPSTAN_RUNNING__')) {
            $cacheFile = $cacheDir.'/.phpstan-dba-'.$reflector.'.cache';
        }

        $reflectionCache = ReflectionCache::create(
            $cacheFile
        );

        if ('recording' === $mode || 'replay-and-recording' === $mode) {
            $schemaHasher = null;

            if ('mysqli' === $reflector) {
                $mysqli = new mysqli($host, $user, $password, $dbname);
                $reflector = new MysqliQueryReflector($mysqli);
                $schemaHasher = new SchemaHasherMysql($mysqli);
            } elseif ('pdo-mysql' === $reflector) {
                $pdo = new PDO(sprintf('mysql:dbname=%s;host=%s', $dbname, $host), $user, $password);
                $reflector = new PdoMysqlQueryReflector($pdo);
                $schemaHasher = new SchemaHasherMysql($pdo);
            } elseif ('pdo-pgsql' === $reflector) {
                $pdo = new PDO(sprintf('pgsql:dbname=%s;host=%s', $dbname, $host), $user, $password);
                $reflector = new PdoPgSqlQueryReflector($pdo);
            } else {
                throw new \RuntimeException('Unknown reflector: '.$reflector);
            }

            if ('replay-and-recording' === $mode) {
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
        } elseif ('replay' === $mode) {
            $reflector = new ReplayQueryReflector(
                $reflectionCache
            );
        } else {
            throw new \RuntimeException('Unknown mode: '.$mode);
        }

        return $reflector;
    }
}
