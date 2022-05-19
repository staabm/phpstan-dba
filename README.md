# PHPStan static analysis and type inference for the database access layer

`phpstan-dba` makes your phpstan static code analysis jobs aware of datatypes within your database.
With this information at hand `phpstan-dba` is able to detect type inconsistencies between your domain model and database-schema.
Additionally errors in code handling the results of sql queries can be detected.

This extension provides the following features:

* inspect sql queries, detect errors and placeholder/bound value mismatches
* result set type-inferrence
* builtin support for `doctrine/dbal`, `mysqli`, and `PDO`
* API to built the same features for your custom sql based database access layer

In case you are using Doctrine ORM, you might use phpstan-dba in tandem with [phpstan-doctrine](https://github.com/phpstan/phpstan-doctrine).

**Note:**
At the moment only mysql/mariadb and pgsql databases are supported. Technically it's not a big problem to support other databases though.

[see the unit-testsuite](https://github.com/staabm/phpstan-dba/tree/main/tests/default/data) to get a feeling about the current featureset.


## DEMO

see the ['Files Changed' tab of the DEMO-PR](https://github.com/staabm/phpstan-dba/pull/61/files#diff-98a3c43049f6a0c859c0303037d9773534396533d7890bad187d465d390d634e) for a quick glance.

## Installation

**First**, use composer to install:

```shell
composer require --dev staabm/phpstan-dba
```

**Second**, create a `phpstan-dba-bootstrap.php` file, which allows to you to configure `phpstan-dba` (this optionally includes database connection details, to introspect the database; if you would rather not do this see [Record and Replay](#record-and-replay) below):

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

// TODO: Put your database credentials here
$mysqli = new mysqli('hostname', 'username', 'password', 'database');

QueryReflection::setupReflector(
    new ReplayAndRecordingQueryReflector(
        ReflectionCache::create(
            $cacheFile
        ),
        // XXX alternatively you can use PdoQueryReflector instead
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
- [MySQL Support](https://github.com/staabm/phpstan-dba/blob/main/docs/mysql.md)
- [PGSQL Support](https://github.com/staabm/phpstan-dba/blob/main/docs/pgsql.md)
- [Record and Replay](https://github.com/staabm/phpstan-dba/blob/main/docs/record-and-replay.md)
- [Custom Query APIs Support](https://github.com/staabm/phpstan-dba/blob/main/docs/rules.md)

## Reflector Overview

### Backend Connecting Reflector

These reflectors connect to a real database, infer types based on result-set/schema metadata and are able to detect errors within a given sql query.
It is **not** mandatory to use the same database driver for phpstan-dba, as you use within your application code.

| Reflector            | Key Features                                                                                                                                                 |
|----------------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------|
| MysqliQueryReflector | - limited to mysql/mariadb databases<br/>- requires a active database connection<br/>- most feature complete reflector                                       |
| PdoQueryReflector    | - connects to a mysql/mariadb database<br/>- requires a active database connection<br/>- can be used as a foundation for other database types in the future  |

### Utility Reflectors

Utility reflectors will be used in combination with backend connecting reflectors to provide additional features.

| Reflector                        | Key Features                                                                                                                                                                                                                                                                                                                                                |
|----------------------------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| ReplayAndRecordingQueryReflector | - wraps a backend connecting reflector, caches the reflected information into a local file and utilizes the cached information<br/>- will re-validate the cached information<br/>- will update local cache file information, even on external changes<br/>- will reduce database interactions to a minimum, but still requires a active database connection |
| ReplayQueryReflector             | - utilizes the cached information of a `*RecordingQueryReflector`<br/>- will **not** validate the cached information, therefore might return stale results<br/> - does **not** require a active database connection                                                                                                                                         |
| ChainedReflector                 | - chain several backend connecting reflectors, so applications which use multiple database connections can be analyzed                                                                                                                                                                                                                                      |

Legacy utility reflectors

| Reflector                        | Key Features                                                                                         |
|----------------------------------|------------------------------------------------------------------------------------------------------|
| RecordingQueryReflector          | - wraps a backend connecting reflector and caches the reflected information into a local file<br/>-requires a active database connection  |

