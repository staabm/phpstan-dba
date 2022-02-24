<?php

namespace staabm\PHPStanDba\Tests;

use mysqli;
use PDO;
use staabm\PHPStanDba\DbSchema\SchemaHasherMysql;
use staabm\PHPStanDba\QueryReflection\MysqliQueryReflector;
use staabm\PHPStanDba\QueryReflection\PdoQueryReflector;
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
            $host = $_ENV['DBA_HOST'] ?: 'mysql80.ab';
            $user = $_ENV['DBA_USER'] ?: null;
            $password = $_ENV['DBA_PASSWORD'] ?: null;
            $dbname = $_ENV['DBA_DATABASE'] ?: 'phpstan_dba';
            $mode = $_ENV['DBA_MODE'] ?: 'recording';
            $reflector = $_ENV['DBA_REFLECTOR'] ?: 'mysqli';
        }

        // make env vars available to tests, in case non are defined yet
        $_ENV['DBA_REFLECTOR'] = $reflector;

        // we need to record the reflection information in both, phpunit and phpstan since we are replaying it in both CI jobs.
        // in a regular application you will use phpstan-dba only within your phpstan CI job, therefore you only need 1 cache-file.
        // phpstan-dba itself is a special case, since we are testing phpstan-dba with phpstan-dba.
        $cacheFile = $cacheDir.'/.phpunit-phpstan-dba-'.$reflector.'.cache';
        if (\defined('__PHPSTAN_RUNNING__')) {
            $cacheFile = $cacheDir.'/.phpstan-dba-'.$reflector.'.cache';
        }

        $reflectionCache = ReflectionCache::create(
            $cacheFile
        );

        if ('recording' === $mode || 'replay-and-recording' === $mode) {
            if ('mysqli' === $reflector) {
                $mysqli = new mysqli($host, $user, $password, $dbname);
                $reflector = new MysqliQueryReflector($mysqli);
                $schemaHasher = new SchemaHasherMysql($mysqli);
            } elseif ('pdo' === $reflector) {
                $pdo = new PDO(sprintf('mysql:dbname=%s;host=%s', $dbname, $host), $user, $password);
                $reflector = new PdoQueryReflector($pdo);
                $schemaHasher = new SchemaHasherMysql($pdo);
            } else {
                throw new \RuntimeException('Unknown reflector: '.$reflector);
            }

            if ('replay-and-recording' === $mode) {
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
