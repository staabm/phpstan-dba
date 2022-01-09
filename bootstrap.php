<?php

use staabm\PHPStanDba\QueryReflection\RuntimeConfiguration;
use staabm\PHPStanDba\QueryReflection\MysqliQueryReflector;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\QueryReflection\RecordingQueryReflector;
use staabm\PHPStanDba\QueryReflection\ReplayQueryReflector;
use staabm\PHPStanDba\QueryReflection\ReflectionCache;

require_once __DIR__ . '/vendor/autoload.php';

$cacheFile = __DIR__.'/.phpstan-dba.cache';

try {
	if (false !== getenv('GITHUB_ACTION')) {
		$mysqli = @new mysqli('127.0.0.1', 'root', 'root', 'phpstan_dba');
	} else {
		$mysqli = @new mysqli('mysql80.ab', 'testuser', 'test', 'phpstan_dba');
	}

	$reflector = new MysqliQueryReflector($mysqli);
	// record only while phpstan is running - since this file might also be used as phpunit bootscript
	if (defined('__PHPSTAN_RUNNING__')) {
		$reflector = new RecordingQueryReflector(
			ReflectionCache::create(
				$cacheFile
			),
			$reflector
		);
	}

} catch (mysqli_sql_exception $e) {
	if ($e->getCode() !== MysqliQueryReflector::MYSQL_HOST_NOT_FOUND) {
		throw $e;
	}

	echo "\nWARN: Could not connect to MySQL.\nUsing cached reflection.\n";

	// when we can't connect to the database, we rely on replaying pre-recorded db-reflection information
	$reflector = new ReplayQueryReflector(
		ReflectionCache::load(
			$cacheFile
		)
	);
}

QueryReflection::setupReflector(
	$reflector,
	RuntimeConfiguration::create()->errorMode(RuntimeConfiguration::ERROR_MODE_EXCEPTION)
);
