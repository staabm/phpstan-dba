<?php

use staabm\PHPStanDba\QueryReflection\LazyQueryReflector;
use staabm\PHPStanDba\QueryReflection\MysqliQueryReflector;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\QueryReflection\RecordReplayQueryReflector;

require_once __DIR__ . '/vendor/autoload.php';

if (false !== getenv('GITHUB_ACTION')) {
	// within github actions, we don't have a mysql setup.. rely replaying pre-recorded db-reflection information
	QueryReflection::setupReflector(new RecordReplayQueryReflector(
		__DIR__.'/.phpstan-dba.cache',
		new LazyQueryReflector(function () {
			return new MysqliQueryReflector(new mysqli('mysql57.ab', 'testuser', 'test', 'logitel_clxmobilenet'));
		})
	));
} else {
	QueryReflection::setupReflector(
		new LazyQueryReflector(function () {
			return new MysqliQueryReflector(new mysqli('mysql57.ab', 'testuser', 'test', 'logitel_clxmobilenet'));
		})
	);
}
