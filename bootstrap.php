<?php

use staabm\PHPStanDba\QueryReflection\LazyQueryReflector;
use staabm\PHPStanDba\QueryReflection\MysqliQueryReflector;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\QueryReflection\RecordingQueryReflector;
use staabm\PHPStanDba\QueryReflection\ReplayQueryReflector;
use staabm\PHPStanDba\QueryReflection\ReflectionCache;

require_once __DIR__ . '/vendor/autoload.php';

$cacheFile = __DIR__.'/.phpstan-dba.cache';

if (false !== getenv('GITHUB_ACTION')) {
	// within github actions, we don't have a mysql setup.. rely replaying pre-recorded db-reflection information
	QueryReflection::setupReflector(
		new ReplayQueryReflector(
			ReflectionCache::load(
				$cacheFile
			)
		)
	);
} else {
	QueryReflection::setupReflector(
		new RecordingQueryReflector(
			ReflectionCache::create(
				$cacheFile
			),
			new MysqliQueryReflector(new mysqli('mysql57.ab', 'testuser', 'test', 'phpstan-dba'))
		)
	);
}
