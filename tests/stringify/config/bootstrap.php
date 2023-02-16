<?php

use Dotenv\Dotenv;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\QueryReflection\RuntimeConfiguration;
use staabm\PHPStanDba\Tests\ReflectorFactory;

require_once __DIR__.'/../../../vendor/autoload.php';

if (false === getenv('GITHUB_ACTION')) {
    $dotenv = Dotenv::createImmutable(__DIR__.'/../../..');
    $dotenv->load();
}

$config = RuntimeConfiguration::create();
$config->errorMode(RuntimeConfiguration::ERROR_MODE_EXCEPTION);
$config->stringifyTypes(true);
if (\PHP_VERSION_ID >= 70400) {
    $config->utilizeSqlAst(true);
}

$reflector = ReflectorFactory::create(__DIR__);

QueryReflection::setupReflector(
    $reflector,
    $config
);
