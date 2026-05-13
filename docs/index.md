# phel-sql docs

A small library with a focused job: turn a Phel map into a `[sql params]` tuple. Pure functions, no I/O, no database driver.

## Start here

- **[Quickstart](quickstart.md)**: get a query running in 60 seconds.
- **[Clauses](clauses.md)**: reference for every clause the compiler accepts.
- **[Parameters](parameters.md)**: identifier rules, value placeholders, escape hatches.
- **[Contributing](contributing.md)**: repo layout, how to add a new clause.

## Mental model

```
phel map  ->  sql/format  ->  [sql-string, params-vector]
```

The function does only that. It does not execute, prepare, escape, connect to a database, or know what dialect you target. Pair it with a PDO wrapper (or anything else that takes a prepared statement + positional params) to actually run the query.

## What is here, what is not

| Clause     | MVP | Notes                                  |
|------------|-----|----------------------------------------|
| `:select`  | yes | columns, aliases, qualified keywords   |
| `:from`    | yes | tables, aliases                        |
| `:where`   | yes | `= != < > <= >= LIKE`, AND/OR/NOT, IN, BETWEEN, IS [NOT] NULL |
| `:order-by`| yes | bare col, `[col :asc\|:desc]`         |
| `:limit`   | yes | non-negative integer                   |
| `:offset`  | yes | non-negative integer                   |
| `:join`    | no  | planned                                |
| `:group-by`| no  | planned                                |
| `:having`  | no  | planned                                |
| `:with`    | no  | planned                                |

Inserts, updates, deletes are out of scope until the read DSL is stable.
