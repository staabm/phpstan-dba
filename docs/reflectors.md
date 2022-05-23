# Reflector Overview

## Backend Connecting Reflector

These reflectors connect to a real database, infer types based on result-set/schema metadata and are able to detect errors within a given sql query.
It is **not** mandatory to use the same database driver for phpstan-dba, as you use within your application code.

| Reflector            | Key Features                                                                                                                                                 |
|----------------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------|
| MysqliQueryReflector | - limited to mysql/mariadb databases<br/>- requires a active database connection<br/>- most feature complete reflector                                       |
| PdoMysqlQueryReflector    | - connects to a mysql/mariadb database<br/>- requires a active database connection |
| PdoPgSqlQueryReflector    | - connects to a PGSQL database<br/>- requires a active database connection |


## Utility Reflectors

Utility reflectors will be used in combination with backend connecting reflectors to provide additional features.

| Reflector                        | Key Features                                                                                                                                                                                                                                                                                                                                                |
|----------------------------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| ReplayAndRecordingQueryReflector | - wraps a backend connecting reflector, caches the reflected information into a local file and utilizes the cached information<br/>- will re-validate the cached information<br/>- will update local cache file information, even on external changes<br/>- will reduce database interactions to a minimum, but still requires a active database connection |
| RecordingQueryReflector          | - wraps a backend connecting reflector and caches the reflected information into a local file<br/>- requires a active database connection  |
| ReplayQueryReflector             | - utilizes the cached information of a `*RecordingQueryReflector`<br/>- will **not** validate the cached information, therefore might return stale results<br/> - does **not** require a active database connection                                                                                                                                         |
| ChainedReflector                 | - chain several backend connecting reflectors, so applications which use multiple database connections can be analyzed                                                                                                                                                                                                                                      |
