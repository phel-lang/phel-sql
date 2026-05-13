# phel-sql docs

A small library with a focused job: turn a Phel map into a `[sql params]` tuple. Pure functions, no I/O, no database driver.

## Start here

- **[Quickstart](quickstart.md)**: get a query running in 60 seconds.
- **[Clauses](clauses.md)**: every clause and operator.
- **[Parameters](parameters.md)**: identifier rules, values, subqueries, raw fragments.
- **[Contributing](contributing.md)**: repo layout, how to add a new clause.

## Mental model

```
phel map  ->  sql/format  ->  [sql-string, params-vector]
```

The function does only that. It does not execute, prepare, escape, connect to a database, or know which dialect you target. Pair it with a PDO wrapper (or anything else that takes a prepared statement + positional params) to actually run the query.

## Statement detection

The compiler looks at the keys in your map and picks one of five statement kinds:

| Trigger key                                          | Statement |
|------------------------------------------------------|-----------|
| `:insert-into`                                       | INSERT    |
| `:update`                                            | UPDATE    |
| `:delete-from`                                       | DELETE    |
| `:union` / `:union-all` / `:intersect` / `:except`   | set op    |
| (none of the above)                                  | SELECT    |

Each kind has its own permitted clauses. Mixing them raises `InvalidArgumentException`.

## Coverage at a glance

| Capability                                | Supported |
|-------------------------------------------|-----------|
| SELECT (with all clauses below)           | yes       |
| WITH / WITH RECURSIVE (CTEs)              | yes       |
| SELECT DISTINCT                           | yes       |
| FROM, joins (inner/left/right/full/cross) | yes       |
| WHERE (16 operators incl. EXISTS, NOT IN) | yes       |
| GROUP BY, HAVING                          | yes       |
| ORDER BY, LIMIT, OFFSET                   | yes       |
| FOR UPDATE / FOR SHARE (+ NOWAIT / SKIP LOCKED) | yes |
| RETURNING                                 | yes       |
| Subqueries everywhere                     | yes       |
| Raw SQL escape hatch `[:raw "..."]`       | yes       |
| INSERT (vector rows or map rows)          | yes       |
| UPDATE                                    | yes       |
| DELETE                                    | yes       |
| Top-level UNION / INTERSECT / EXCEPT      | yes       |
| Dialect-specific extensions (UPSERT, JSON ops, window functions) | no |
| Named parameters (`:foo`)                 | not yet   |

Window functions, MERGE, vendor-specific UPSERT and JSON syntax are reachable through `[:raw "..."]` until first-class support lands.
