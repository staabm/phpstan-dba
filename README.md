# database handling class reflection extension for PHPStan

phpstan-dba makes your phpstan static code analysis jobs aware of datatypes within your database.
With this information at hand phpstan-dba is able to detect type inconsistencies between your domain model and database-schema.
Additionally errors in code handling the results of sql queries can be detected.

This extension provides following features:

* the array shape of results can be inferred for `PDOStatement` and `mysqli_result`
  * .. when the query string can be resolved at analysis time. This is even possible for queries containing php-variables, as long as their typ is known in most cases.
  * builtin we support `mysqli_query`, `mysqli->query`, `PDOStatement->fetch`, `PDOStatement->fetchAll`, `PDOStatement->execute`, `PDO->query` and `PDO->prepare`
* `SyntaxErrorInQueryMethodRule` can inspect sql queries and detect syntax errors - `SyntaxErrorInQueryFunctionRule` can do the same for functions
  * builtin is query syntax error detection for `mysqli_query`, `mysqli->query`, `PDO->query` and `PDO->prepare`
* `mysqli_real_escape_string` and `mysqli->real_escape_string` dynamic return type extensions
* `pdo->quote` dynamic return type extension

[see the unit-testsuite](https://github.com/staabm/phpstan-dba/tree/main/tests/data) to get a feeling about the current featureset.

__Its really early days... and this libs has a few rough edges.__

## DEMO

see the ['Files Changed' tab of the DEMO-PR](https://github.com/staabm/phpstan-dba/pull/61/files#diff-98a3c43049f6a0c859c0303037d9773534396533d7890bad187d465d390d634e) for a quick glance.

## Usage

To get the extension running you need to configure the `phpstan-dba`.

1. If you also install [phpstan/extension-installer](https://github.com/phpstan/extension-installer) proceed with step 2.
<details>
  <summary>Manual installation</summary>

  If you don't want to use `phpstan/extension-installer`, include extension.neon in your project's PHPStan config:

    ```
    includes:
        - vendor/staabm/phpstan-dba/config/dba.neon
    ```
</details>

2. Additionally your `bootstrap` file needs to be [configured within your phpstan configuration](https://phpstan.org/config-reference#bootstrap), so it will be automatically included by PHPStan:

```php
<?php // bootstrap.php

use staabm\PHPStanDba\QueryReflection\RuntimeConfiguration;
use staabm\PHPStanDba\QueryReflection\MysqliQueryReflector;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\QueryReflection\RecordingQueryReflector;
use staabm\PHPStanDba\QueryReflection\ReplayQueryReflector;
use staabm\PHPStanDba\QueryReflection\ReflectionCache;

require_once __DIR__ . '/vendor/autoload.php';

$cacheFile = __DIR__.'/.phpstan-dba.cache';

QueryReflection::setupReflector(
    new RecordingQueryReflector(
        ReflectionCache::create(
            $cacheFile
        ),
        // XXX put your database credentials here
        new MysqliQueryReflector(new mysqli('mysql57.ab', 'testuser', 'test', 'phpstan-dba'))
    )
);
```

As you can see, `phpstan-dba` requires a `mysqli` connection to introspect the database.

### Record and Replay

In case you don't want to depend on a database at PHPStan analysis time, you can use the [`RecordingQueryReflector`](https://github.com/staabm/phpstan-dba/blob/main/src/QueryReflection/RecordingQueryReflector.php) to record the reflection information.
With this cache file you can utilize [`ReplayQueryReflector`](https://github.com/staabm/phpstan-dba/blob/main/src/QueryReflection/ReplayQueryReflector.php) to replay the reflection information, without the need for a active database connection.

```php
<?php // bootstrap.php

use staabm\PHPStanDba\QueryReflection\RuntimeConfiguration;
use staabm\PHPStanDba\QueryReflection\MysqliQueryReflector;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\QueryReflection\RecordingQueryReflector;
use staabm\PHPStanDba\QueryReflection\ReplayQueryReflector;
use staabm\PHPStanDba\QueryReflection\ReflectionCache;

require_once __DIR__ . '/vendor/autoload.php';

$cacheFile = __DIR__.'/.phpstan-dba.cache';

QueryReflection::setupReflector(
    new ReplayQueryReflector(
        ReflectionCache::load(
            $cacheFile
        )
    )
);
```

This might be usefull if your CI pipeline cannot connect to your development database server for whatever reason.

The GitHubActions setup of `phpstan-dba` is [using this technique to replay the reflection information](https://github.com/staabm/phpstan-dba/blob/main/bootstrap.php).

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

## Runtime configuration

Within your phpstan-bootstrap file you can configure phpstan-dba so it knows about global runtime configuration state, which cannot be detect automatically.
Use the [`RuntimeConfiguration`](https://github.com/staabm/phpstan-dba/tree/main/src/QueryReflection/RuntimeConfiguration.php) builder-object and pass it as a second argument to `QueryReflection::setupReflector()`.

If not configured otherwise, the following defaults are used:
- when analyzing a php8+ codebase, [`PDO::ERRMODE_EXCEPTION` error handling](https://www.php.net/manual/en/pdo.error-handling.php) is assumed.
- when analyzing a php8.1+ codebase, [`mysqli_report(\MYSQLI_REPORT_ERROR | \MYSQLI_REPORT_STRICT);` error handling](https://www.php.net/mysqli_report) is assumed.

## Installation

```shell
composer require --dev staabm/phpstan-dba
```

## Caveats

- running `RecordingQueryReflector` requires running PHPStan with `--debug` (otherwise we see concurrency issues while building the cache).

## Todos

- support [more mysql to PHPStan type mappings](https://github.com/staabm/phpstan-dba/blob/b868f40c80afcecd3de408df3801b5a24e220dd8/src/QueryReflection/MysqliQueryReflector.php#L111)
- cover more real world examples and fine tune the [QueryReflection classes](https://github.com/staabm/phpstan-dba/tree/main/src/QueryReflection)
- support a PDO based `QueryReflector`
- security rule: detect possible sql injections
- performance rule: detect queries not using indexes
