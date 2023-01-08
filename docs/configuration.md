# Runtime configuration

Within your `phpstan-dba-bootstrap.php` file you can configure `phpstan-dba` so it knows about global runtime configuration state, which cannot be detect automatically.
Use the [`RuntimeConfiguration`](https://github.com/staabm/phpstan-dba/tree/main/src/QueryReflection/RuntimeConfiguration.php) builder-object and pass it as a second argument to `QueryReflection::setupReflector()`.

If not configured otherwise, the following defaults are used:
- type-inference works as precise as possible. In case your database access layer returns strings instead of integers and floats, use the [`stringifyTypes`](https://github.com/staabm/phpstan-dba/tree/main/src/QueryReflection/RuntimeConfiguration.php) option.
- when analyzing a php8+ codebase, [`PDO::ERRMODE_EXCEPTION` error handling](https://www.php.net/manual/en/pdo.error-handling.php) is assumed.
- when analyzing a php8.1+ codebase, [`mysqli_report(\MYSQLI_REPORT_ERROR | \MYSQLI_REPORT_STRICT);` error handling](https://www.php.net/mysqli_report) is assumed.
- the fetch mode defaults to `QueryReflector::FETCH_TYPE_BOTH`, but can be configured using the [`defaultFetchMode`](https://github.com/staabm/phpstan-dba/tree/main/src/QueryReflection/RuntimeConfiguration.php) option.
- read and write queries are analyzed per default. you may consider disable write query analysis using the [`analyzeWriteQueries`](https://github.com/staabm/phpstan-dba/tree/main/src/QueryReflection/RuntimeConfiguration.php) option.
