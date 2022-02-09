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
        if (false !== getenv('GITHUB_ACTION')) {
            $host = '127.0.0.1';
            $user = 'root';
            $password = 'root';
            $dbname = 'phpstan_dba';
        } else {
            $host = 'mysql80.ab';
            $user = 'testuser';
            $password = 'test';
            $dbname = 'phpstan_dba';
        }

        $mode = getenv('DBA_MODE') ?: 'recording';
        $reflector = getenv('DBA_REFLECTOR') ?: 'mysqli';
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
