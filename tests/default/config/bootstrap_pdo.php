<?php

use staabm\PHPStanDba\QueryReflection\PDOQueryReflector;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\QueryReflection\RecordingQueryReflector;
use staabm\PHPStanDba\QueryReflection\ReflectionCache;
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

if (false !== getenv('GITHUB_ACTION')) {
    $mysqli = @new mysqli('127.0.0.1', 'root', 'root', 'phpstan_dba');
} else {
    $mysqli = new mysqli('localhost', 'root', 'test22', 'phpstan_dba');
    $pdo = new PDO(
        'mysql:dbname=phpstan_dba;host=localhost',
        'root',
        'test22'
    );
}

$reflector = new PDOQueryReflector($pdo);
$reflector = new RecordingQueryReflector(
    ReflectionCache::create(
        $cacheFile
    ),
    $reflector
);


QueryReflection::setupReflector(
    $reflector,
    $config
);
