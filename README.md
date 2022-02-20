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
At the moment only mysql/mariadb databases are supported. Technically it's not a big problem to support other databases though.

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

## Runtime configuration

Within your `phpstan-dba-bootstrap.php` file you can configure `phpstan-dba` so it knows about global runtime configuration state, which cannot be detect automatically.
Use the [`RuntimeConfiguration`](https://github.com/staabm/phpstan-dba/tree/main/src/QueryReflection/RuntimeConfiguration.php) builder-object and pass it as a second argument to `QueryReflection::setupReflector()`.

If not configured otherwise, the following defaults are used:
- type-inference works as precise as possible. In case your database access layer returns strings instead of integers and floats, use the [`stringifyTypes`](https://github.com/staabm/phpstan-dba/tree/main/src/QueryReflection/RuntimeConfiguration.php) option.
- when analyzing a php8+ codebase, [`PDO::ERRMODE_EXCEPTION` error handling](https://www.php.net/manual/en/pdo.error-handling.php) is assumed.
- when analyzing a php8.1+ codebase, [`mysqli_report(\MYSQLI_REPORT_ERROR | \MYSQLI_REPORT_STRICT);` error handling](https://www.php.net/mysqli_report) is assumed.
- the fetch mode defaults to `QueryReflector::FETCH_TYPE_BOTH`, but can be configured using the [`defaultFetchMode`](https://github.com/staabm/phpstan-dba/tree/main/src/QueryReflection/RuntimeConfiguration.php) option.

### Record and Replay

In case you don't want to depend on a database at PHPStan analysis time, you can use one of the `*RecordingQueryReflector`-classes to record the reflection information.

With this cache file you can utilize [`ReplayQueryReflector`](https://github.com/staabm/phpstan-dba/blob/main/src/QueryReflection/ReplayQueryReflector.php) to replay the reflection information, without the need for a active database connection.

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

| Reflector                        | Key Features                                                                                                                                                                                                                                                                                                                                             |
|----------------------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| ReplayAndRecordingQueryReflector | - wraps a backend connecting reflector, caches the reflected information into a local file and utilizes the cached information<br/>- will validate the cached information<br/>- will update local cache file information, even on external changes<br/>- will reduce database interactions to a minimum, but still requires a active database connection |
| RecordingQueryReflector          | - wraps a backend connecting reflector and caches the reflected information into a local file                                                                                                                                                                                                                                                            |
| ReplayQueryReflector             | - utilizes the cached information of a *RecordingQueryReflector<br/>- will **not** validate the cached information, therefore might return stale results<br/> - does **not** require a active database connection                                                                                                                                        |
| ChainedReflector                 | - chain several backend connecting reflectors, so applications which use multiple database connections can be analyzed                                                                                                                                                                                                                                   |

## Advanced Usage

### use `SyntaxErrorInPreparedStatementMethodRule` for your custom classes

Reuse the `SyntaxErrorInPreparedStatementMethodRule` within your PHPStan configuration to detect syntax errors in prepared queries, by registering a service:

```
services:
    -
        class: staabm\PHPStanDba\Rules\SyntaxErrorInPreparedStatementMethodRule
        tags: [phpstan.rules.rule]
        arguments:
            classMethods:
                - 'My\Connection::preparedQuery'
                - 'My\PreparedStatement::__construct'
```

__the callable format is `class::method`. phpstan-dba assumes the method takes a query-string as a 1st and the parameter-values as a 2nd argument.__

### use `SyntaxErrorInQueryMethodRule` for your custom classes

Reuse the `SyntaxErrorInQueryMethodRule` within your PHPStan configuration to detect syntax errors in queries, by registering a service:

```
services:
    -
        class: staabm\PHPStanDba\Rules\SyntaxErrorInQueryMethodRule
        tags: [phpstan.rules.rule]
        arguments:
            classMethods:
                - 'myClass::query#0'
                - 'anotherClass::takesAQuery#2'
```

__the callable format is `class::method#parameterIndex`, while the parameter-index defines the position of the query-string argument.__

### use `SyntaxErrorInQueryFunctionRule` for your custom functions

Reuse the `SyntaxErrorInQueryFunctionRule` within your PHPStan configuration to detect syntax errors in queries, by registering a service:

```
services:
    -
        class: staabm\PHPStanDba\Rules\SyntaxErrorInQueryFunctionRule
        tags: [phpstan.rules.rule]
        arguments:
            functionNames:
                - 'Deployer\runMysqlQuery#0'
```

__the callable format is `funtionName#parameterIndex`, while the parameter-index defines the position of the query-string argument.__
