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

__the callable format is `funtionName#parameterIndex`, while the parameter-index defines the position of the query-string argument.__

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
