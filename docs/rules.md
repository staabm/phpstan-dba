# Advanced Usage / Re-Using `phpstan-dba` PHPStan for custom query APIs

For custom/non-standard query APIs the PHPStan rules shipped with `phpstan-dba` can be configured.

## use `SyntaxErrorInPreparedStatementMethodRule` for your custom classes

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

## use `SyntaxErrorInQueryMethodRule` for your custom classes

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

## use `SyntaxErrorInQueryFunctionRule` for your custom functions

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

__the callable format is `functionName#parameterIndex`, while the parameter-index defines the position of the query-string argument.__

## use `DoctrineKeyValueStyleRule` for your custom classes

Reuse the `DoctrineKeyValueStyleRule` within your PHPStan configuration to detect syntax errors in class methods that construct queries from table and column name variables, by registering a service:
The rule is designed around doctrine's [data-retrieval-and-manipulation-api](https://www.doctrine-project.org/projects/doctrine-dbal/en/current/reference/data-retrieval-and-manipulation.html#insert).

```
services:
    -
        class: staabm\PHPStanDba\Rules\DoctrineKeyValueStyleRule
        tags: [phpstan.rules.rule]
        arguments:
            classMethods:
                - 'My\Connection::truncate'
                - 'My\Connection::insert#1'
                - 'My\Connection::delete#1'
                - 'My\Connection::update#1,2'
```

__the callable format is `class::method#arrayArgIndex,arrayArgIndex`. phpstan-dba assumes the method takes a table name as the 1st argument and any listed argument indices are arrays with column name keys and column value values.__


## use `QueryPlanAnalyzerRule` for your custom classes

Reuse the `QueryPlanAnalyzerRule` within your PHPStan configuration to detect syntax errors in queries, by registering a service:

    -
        class: staabm\PHPStanDba\Rules\QueryPlanAnalyzerRule
        tags: [phpstan.rules.rule]
        arguments:
            classMethods:
                - 'myClass::query#0'
                - 'anotherClass::takesAQuery#2'

__the callable format is `class::method#parameterIndex`, while the parameter-index defines the position of the query-string argument.__

see also [Query Plan Analysis](https://github.com/staabm/phpstan-dba/blob/main/docs/query-plan-analysis.md)
