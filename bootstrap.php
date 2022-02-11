<?php

use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\QueryReflection\RuntimeConfiguration;
use staabm\PHPStanDba\Tests\ReflectorFactory;

require_once __DIR__.'/vendor/autoload.php';

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

$reflector = ReflectorFactory::create($cacheFile);

QueryReflection::setupReflector(
    $reflector,
    $config
);
