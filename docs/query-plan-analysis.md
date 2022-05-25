# Query Plan Analysis

Within your `phpstan-dba-bootstrap.php` file, you can optionally enable query plan analysis.
When enabled, `phpstan-dba` will error when queries are not using indices or queries are inefficient.

The analyzer is reporting problems related to queries not using index, full-table-scans and too many unindexed reads.

Passing `true` will enable the feature:

```php
$config = new RuntimeConfiguration();
$config->analyzeQueryPlans(true);
```

For more fine grained control, you can pass a positive-integer describing the number of unindexed reads a query is allowed to execute before being considered inefficient.
This will only affect queries which already use an index.

```php
$config = new RuntimeConfiguration();
$config->analyzeQueryPlans(100000);
```

To disable the effiency analysis but just check for queries not using indices at all, pass `0`.

```php
$config = new RuntimeConfiguration();
$config->analyzeQueryPlans(0);
```

**Note:** For a meaningful performance analysis it is vital to utilize a database, which containts data and schema as similar as possible to the production database.

**Note:** "Query Plan Analysis" requires an active database connection.

**Note:** ["Query Plan Analysis" is not yet supported on the PGSQL driver](https://github.com/staabm/phpstan-dba/issues/378)
