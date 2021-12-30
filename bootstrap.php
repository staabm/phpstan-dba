<?php

use staabm\PHPStanDba\QueryReflection\MysqliQueryReflector;

require_once __DIR__ . '/vendor/autoload.php';

MysqliQueryReflector::setupConnection(new mysqli('mysql57.ab', 'testuser', 'test', 'logitel_clxmobilenet'));
