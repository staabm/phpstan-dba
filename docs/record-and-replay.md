# Record and Replay

In case you don't want to depend on a database at PHPStan analysis time, you can use one of the `*RecordingQueryReflector`-classes to record the reflection information. To ease configuration a `ReplayAndRecordingQueryReflector` is supported.

With this cache file you can utilize [`ReplayQueryReflector`](https://github.com/staabm/phpstan-dba/blob/main/src/QueryReflection/ReplayQueryReflector.php) to replay the reflection information, without the need for a active database connection.

**Note:** ["Record and Replay" is not yet supported on the PGSQL driver](https://github.com/staabm/phpstan-dba/issues/353)

```php
<?php // phpstan-dba-bootstrap.php

use staabm\PHPStanDba\QueryReflection\RuntimeConfiguration;
use staabm\PHPStanDba\QueryReflection\MysqliQueryReflector;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\QueryReflection\RecordingQueryReflector;
use staabm\PHPStanDba\QueryReflection\ReplayQueryReflector;
use staabm\PHPStanDba\QueryReflection\ReflectionCache;

require_once __DIR__ . '/vendor/autoload.php';

$cacheFile = __DIR__.'/.phpstan-dba.cache';

$config = new RuntimeConfiguration();
// $config->debugMode(true);
// $config->stringifyTypes(true);
// $config->analyzeQueryPlans(true);

QueryReflection::setupReflector(
    new ReplayQueryReflector(
        ReflectionCache::create(
            $cacheFile
        )
    ),
    $config
);
```

This might be usefull if your CI pipeline can't/shouldn't connect to your development database server for whatever reason.

**Note**: In case you commit the generated cache files into your repository, consider [marking them as generated files](https://docs.github.com/en/repositories/working-with-files/managing-files/customizing-how-changed-files-appear-on-github), so they don't show up in Pull Requests.
