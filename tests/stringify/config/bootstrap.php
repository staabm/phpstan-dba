<?php

use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\QueryReflection\RuntimeConfiguration;
use staabm\PHPStanDba\Tests\ReflectorFactory;

require_once __DIR__.'/../../../vendor/autoload.php';

$config = RuntimeConfiguration::create();
$config->errorMode(RuntimeConfiguration::ERROR_MODE_EXCEPTION);
$config->stringifyTypes(true);

$reflector = ReflectorFactory::create(__DIR__);

QueryReflection::setupReflector(
    $reflector,
    $config
);
