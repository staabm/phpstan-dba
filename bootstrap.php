<?php

use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\QueryReflection\RuntimeConfiguration;
use staabm\PHPStanDba\Tests\ReflectorFactory;

require_once __DIR__.'/vendor/autoload.php';

$config = RuntimeConfiguration::create();
$config->errorMode(RuntimeConfiguration::ERROR_MODE_EXCEPTION);
// $config->debugMode(true);

if (false === getenv('GITHUB_ACTION')) {
    putenv('DBA_MODE=replay-and-recording');
}

$reflector = ReflectorFactory::create(__DIR__);

QueryReflection::setupReflector(
    $reflector,
    $config
);
