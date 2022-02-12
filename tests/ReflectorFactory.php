<?php

namespace staabm\PHPStanDba\Tests;

use mysqli;
use staabm\PHPStanDba\QueryReflection\MysqliQueryReflector;
use staabm\PHPStanDba\QueryReflection\QueryReflector;
use staabm\PHPStanDba\QueryReflection\RecordingQueryReflector;
use staabm\PHPStanDba\QueryReflection\ReflectionCache;
use staabm\PHPStanDba\QueryReflection\ReplayQueryReflector;

final class ReflectorFactory
{
    public static function create(string $cacheFile): QueryReflector
    {
        // handle defaults
        if (false !== getenv('GITHUB_ACTION')) {
            $host = getenv('DBA_HOST') ?: '127.0.0.1';
            $user = getenv('DBA_USER') ?: 'root';
            $password = getenv('DBA_PASSWORD') ?: 'root';
            $dbname = getenv('DBA_DATABASE') ?: 'phpstan_dba';
        } else {
            $host = getenv('DBA_HOST') ?: 'mysql80.ab';
            $user = getenv('DBA_USER') ?: 'testuser';
            $password = getenv('DBA_PASSWORD') ?: 'test';
            $dbname = getenv('DBA_DATABASE') ?: 'phpstan_dba';
        }

        $mode = getenv('DBA_MODE') ?: 'recording';
        $reflector = getenv('DBA_REFLECTOR') ?: 'mysqli';

        // make env vars available to tests, in case non are defined yet
        putenv('DBA_REFLECTOR='.$reflector);

        if ('recording' === $mode) {
            if ('mysqli' === $reflector) {
                $mysqli = @new mysqli($host, $user, $password, $dbname);
                $reflector = new MysqliQueryReflector($mysqli);
            } else {
                throw new \RuntimeException('Unknown reflector: '.$reflector);
            }

            $reflector = new RecordingQueryReflector(
                ReflectionCache::create(
                    $cacheFile
                ),
                $reflector
            );
        } elseif ('replay' === $mode) {
            $reflector = new ReplayQueryReflector(
                ReflectionCache::create(
                    $cacheFile
                )
            );
        } else {
            throw new \RuntimeException('Unknown mode: '.$mode);
        }

        return $reflector;
    }
}
