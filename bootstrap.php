<?php

declare(strict_types=1);

use Dotenv\Dotenv;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\QueryReflection\RuntimeConfiguration;
use staabm\PHPStanDba\Tests\ReflectorFactory;

require_once __DIR__ . '/vendor/autoload.php';

if (false === getenv('GITHUB_ACTION')) {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

$config = RuntimeConfiguration::create();
$config->errorMode(RuntimeConfiguration::ERROR_MODE_EXCEPTION);
$config->utilizeSqlAst(true);
// $config->debugMode(true);

if (false === getenv('GITHUB_ACTION') && false === getenv('DBA_MODE')) {
    putenv('DBA_MODE=replay-and-recording');
}

$reflector = ReflectorFactory::create(__DIR__);

QueryReflection::setupReflector(
    $reflector,
    $config
);
