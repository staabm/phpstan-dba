# MySQL

Analyzing MySQL based codebases is supported for Doctrine DBAL, PDO and mysqli.

At analysis time you can pick either `MysqliQueryReflector` or `PdoMysqlQueryReflector`.

## Configuration

```php
<?php // phpstan-dba-bootstrap.php

use staabm\PHPStanDba\DbSchema\SchemaHasherMysql;
use staabm\PHPStanDba\QueryReflection\RuntimeConfiguration;
use staabm\PHPStanDba\QueryReflection\MysqliQueryReflector;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\QueryReflection\ReplayAndRecordingQueryReflector;
use staabm\PHPStanDba\QueryReflection\ReplayQueryReflector;
use staabm\PHPStanDba\QueryReflection\ReflectionCache;

require_once __DIR__ . '/vendor/autoload.php';

$cacheFile = __DIR__.'/.phpstan-dba.cache';

$config = new RuntimeConfiguration();
// $config->debugMode(true);
// $config->stringifyTypes(true);
// $config->analyzeQueryPlans(true);

// TODO: Put your database credentials here
$mysqli = new mysqli('hostname', 'username', 'password', 'database');

QueryReflection::setupReflector(
    new ReplayAndRecordingQueryReflector(
        ReflectionCache::create(
            $cacheFile
        ),
        // XXX alternatively you can use PdoMysqlQueryReflector instead
        new MysqliQueryReflector($mysqli),
        new SchemaHasherMysql($mysqli)

    ),
    $config
);
```

## Advanced Usage

For custom/non-standard MySQL query APIs the [PHPStan rules shipped with `phpstandba` can be configured](https://github.com/staabm/phpstan-dba/blob/main/docs/rules.md).
