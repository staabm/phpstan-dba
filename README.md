# `phpstandba`: PHPStan based SQL static analysis and type inference for the database access layer

`phpstan-dba` makes your phpstan static code analysis jobs aware of datatypes within your database.
With this information at hand we are able to detect type inconsistencies between your domain model and database-schema.
Additionally errors in code handling the results of sql queries can be detected.

This extension provides the following features, as long as you [stick to the rules](https://staabm.github.io/2022/07/23/phpstan-dba-inference-placeholder.html#the-golden-phpstan-dba-rules):

* [result set type-inferrence](https://staabm.github.io/2022/06/19/phpstan-dba-type-inference.html)
* [detect errors in sql queries](https://staabm.github.io/2022/08/05/phpstan-dba-syntax-error-detection.html)
* [detect placeholder/bound value mismatches](https://staabm.github.io/2022/07/30/phpstan-dba-placeholder-validation.html)
* [query plan analysis](https://staabm.github.io/2022/08/16/phpstan-dba-query-plan-analysis.html) to detect performance issues
* builtin support for `doctrine/dbal`, `mysqli`, and `PDO`
* API to configure the same features for your custom sql based database access layer
* Opt-In analysis of write queries (since version 0.2.55+)

In case you are using Doctrine ORM, you might use `phpstan-dba` in tandem with [phpstan-doctrine](https://github.com/phpstan/phpstan-doctrine).

**Note:**
At the moment only MySQL/MariaDB and PGSQL databases are supported. Technically it's not a big problem to support other databases though.


## DEMO

see the ['Files Changed' tab of the DEMO-PR](https://github.com/staabm/phpstan-dba/pull/61/files#diff-98a3c43049f6a0c859c0303037d9773534396533d7890bad187d465d390d634e) for a quick glance.

## 💌 Support phpstan-dba

[Consider supporting the project](https://github.com/sponsors/staabm), so we can make this tool even better even faster for everyone.

## Installation

**First**, use composer to install:

```shell
composer require --dev staabm/phpstan-dba
```

**Second**, create a `phpstan-dba-bootstrap.php` file, which allows to you to configure `phpstan-dba` (this optionally includes database connection details, to introspect the database; if you would rather not do this see [Record and Replay](https://github.com/staabm/phpstan-dba/blob/main/docs/record-and-replay.md):

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

*Note*: [Configuration for PGSQL is pretty similar](https://github.com/staabm/phpstan-dba/blob/main/docs/pgsql.md)

**Third**, create or update your `phpstan.neon` file so [bootstrapFiles](https://phpstan.org/config-reference#bootstrap) includes `phpstan-dba-bootstrap.php`.

If you are **not** using [phpstan/extension-installer](https://github.com/phpstan/extension-installer), you will also need to include `dba.neon`.

Your `phpstan.neon` might look something like:

```neon
parameters:
  level: 8
  paths:
    - src/
  bootstrapFiles:
    - phpstan-dba-bootstrap.php

includes:
  - ./vendor/staabm/phpstan-dba/config/dba.neon
```

**Finally**, run `phpstan`, e.g.

```shell
./vendor/bin/phpstan analyse -c phpstan.neon
```

## Read more

- [Runtime configuration](https://github.com/staabm/phpstan-dba/blob/main/docs/configuration.md)
- [Record and Replay](https://github.com/staabm/phpstan-dba/blob/main/docs/record-and-replay.md)
- [Custom Query API Support](https://github.com/staabm/phpstan-dba/blob/main/docs/rules.md)
- [MySQL Support](https://github.com/staabm/phpstan-dba/blob/main/docs/mysql.md)
- [PGSQL Support](https://github.com/staabm/phpstan-dba/blob/main/docs/pgsql.md)
- [Reflector Overview](https://github.com/staabm/phpstan-dba/blob/main/docs/reflectors.md)
- [How to analyze a PHP 7.x codebase?](https://github.com/staabm/phpstan-dba/blob/main/docs/faq.md)
