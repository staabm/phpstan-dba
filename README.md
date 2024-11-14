phpstan bug repro

- checkout the repository
- composer install

`vendor/bin/phpstan analyze src/SqlAst/ParserInference.php --debug`

leads to

```
➜  phpstan-dba git:(staabm-patch-3) ✗ vendor/bin/phpstan analyze src/SqlAst/ParserInference.php --debug
Note: Using configuration file /Users/staabm/workspace/phpstan-dba/phpstan.neon.dist.
/Users/staabm/workspace/phpstan-dba/src/SqlAst/ParserInference.php
 ------ ---------------------------------------------
  Line   ParserInference.php
 ------ ---------------------------------------------
  51     Cannot call method getCondition() on mixed.
         🪪  method.nonObject
  55     Cannot call method getLeft() on mixed.
         🪪  method.nonObject
 ------ ---------------------------------------------
```

-> why does `$from` in `src/SqlAst/ParserInference.php` turn into `mixed`?
