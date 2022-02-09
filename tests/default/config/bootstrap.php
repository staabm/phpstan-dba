<?php

use staabm\PHPStanDba\QueryReflection\MysqliQueryReflector;
use staabm\PHPStanDba\QueryReflection\PDOQueryReflector;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\QueryReflection\RecordingQueryReflector;
use staabm\PHPStanDba\QueryReflection\ReflectionCache;
use staabm\PHPStanDba\QueryReflection\ReplayQueryReflector;
use staabm\PHPStanDba\QueryReflection\RuntimeConfiguration;

require_once __DIR__.'/../../../vendor/autoload.php';

// we need to record the reflection information in both, phpunit and phpstan since we are replaying it in both CI jobs.
// in a regular application you will use phpstan-dba only within your phpstan CI job, therefore you only need 1 cache-file.
// phpstan-dba itself is a special case, since we are testing phpstan-dba with phpstan-dba.
$cacheFile = __DIR__.'/.phpunit-phpstan-dba.cache';
if (defined('__PHPSTAN_RUNNING__')) {
    $cacheFile = __DIR__.'/.phpstan-dba.cache';
}

$config = RuntimeConfiguration::create();
$config->errorMode(RuntimeConfiguration::ERROR_MODE_EXCEPTION);
// $config->debugMode(true);

try {
    if (getenv("DBA_REFLECTOR") === "pdo") {
       $pdo = new PDO('mysql:dbname=phpstan_dba;host=127.0.0.1', 'root', 'root');
       $reflector = new PDOQueryReflector($pdo);
    } elseif (false !== getenv('GITHUB_ACTION')) {
       $mysqli = @new mysqli('127.0.0.1', 'root', 'root', 'phpstan_dba');
       $reflector = new MysqliQueryReflector($mysqli);
    } else {
       $mysqli = new mysqli('localhost', 'root', 'test22', 'phpstan_dba');
       $reflector = new MysqliQueryReflector($mysqli);
    }

    $reflector = new RecordingQueryReflector(
        ReflectionCache::create(
            $cacheFile
        ),
        $reflector
    );
} catch (mysqli_sql_exception $e) {
    if (MysqliQueryReflector::MYSQL_HOST_NOT_FOUND !== $e->getCode()) {
        throw $e;
    }

    echo "\nWARN: Could not connect to MySQL.\nUsing cached reflection.\n";

    // when we can't connect to the database, we rely on replaying pre-recorded db-reflection information
    $reflector = new ReplayQueryReflector(
        ReflectionCache::create(
            $cacheFile
        )
    );
}

QueryReflection::setupReflector(
    $reflector,
    $config
);
