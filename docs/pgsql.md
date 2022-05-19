# PGSQL

PGSQL/PostgresSQL is supported since `phpstandba 0.2.26`.

While the `phpstandba` engine requires `pdo-pgsql` at analysis time, the codebase beeing analyzed can either use Doctrine DBAL or PDO.

## Configuration

```php
<?php // phpstan-dba-bootstrap.php

use staabm\PHPStanDba\QueryReflection\RuntimeConfiguration;
use staabm\PHPStanDba\QueryReflection\PdoPgSqlQueryReflector;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\QueryReflection\RecordingQueryReflector;
use staabm\PHPStanDba\QueryReflection\ReflectionCache;

require_once __DIR__ . '/vendor/autoload.php';

$cacheFile = __DIR__.'/.phpstan-dba.cache';

$config = new RuntimeConfiguration();
// $config->debugMode(true);
// $config->stringifyTypes(true);

// TODO: Put your database credentials here
$pdo = new PDO(..)

QueryReflection::setupReflector(
    new RecordingQueryReflector(
        ReflectionCache::create(
            $cacheFile
        ),
        new PdoPgSqlQueryReflector($pdo)
    ),
    $config
);
```

## Advanced Usage

For custom/non-standard PGSQL query APIs the PHPStan rules shipped with `phpstandba` can be configured.
