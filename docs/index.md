# phel-sql docs

A small library with a focused job: turn a Phel map into a `[sql params]` tuple. Pure functions, no I/O, no database driver.

## Start here

- **[Quickstart](quickstart.md)**: get a query running in 60 seconds.
- **[Clauses](clauses.md)**: every clause, every operator, every error path.
- **[Expressions](expressions.md)**: tagged forms (`:fn`, `:cast`, `:case`, `:over`, `:filter`, `:lateral`, `:raw`).
- **[Parameters](parameters.md)**: identifier rules, values, subqueries, raw fragments.
- **[Contributing](contributing.md)**: repo layout, how to add a clause or operator.

## Mental model

```
phel map  ->  sql/format  ->  [sql-string, params-vector]
```

The function does only that. It does not execute, prepare, escape, connect to a database, or know which dialect you target.

## Statement detection

| Trigger keys                                          | Statement     |
|-------------------------------------------------------|---------------|
| `:insert-into`                                        | INSERT        |
| `:update`                                             | UPDATE        |
| `:delete-from`                                        | DELETE        |
| `:union` / `:union-all` / `:intersect` / `:except`    | set op        |
| `:values` only (no select/from/dml keys)              | VALUES query  |
| (none of the above)                                   | SELECT        |

Each kind has its own permitted clauses. Mixing them raises `InvalidArgumentException`.

## Coverage at a glance

| Capability                                          | Supported |
|-----------------------------------------------------|-----------|
| SELECT                                              | yes       |
| SELECT DISTINCT                                     | yes       |
| SELECT DISTINCT ON (Postgres)                       | yes       |
| WITH / WITH RECURSIVE (CTEs)                        | yes       |
| FROM, joins (inner / left / right / full / cross)   | yes       |
| Join ON clause                                      | yes       |
| Join USING clause                                   | yes       |
| LATERAL joins / from                                | yes       |
| WHERE: 16 operators incl. EXISTS, NOT IN, ILIKE     | yes       |
| GROUP BY, HAVING                                    | yes       |
| ORDER BY (ASC / DESC / NULLS FIRST / NULLS LAST)    | yes       |
| LIMIT, OFFSET                                       | yes       |
| FOR UPDATE / SHARE (+ NOWAIT / SKIP LOCKED)         | yes       |
| RETURNING                                           | yes       |
| Window functions (`:over` + frame DSL)              | yes       |
| FILTER (WHERE ...)                                  | yes       |
| Function calls (`:fn`)                              | yes       |
| CAST                                                | yes       |
| CASE expressions                                    | yes       |
| Subqueries anywhere                                 | yes       |
| Raw SQL escape hatch `[:raw "..."]`                 | yes       |
| INSERT (vector rows or map rows)                    | yes       |
| INSERT ON CONFLICT (Postgres)                       | yes       |
| INSERT ON DUPLICATE KEY UPDATE (MySQL)              | yes       |
| UPDATE                                              | yes       |
| DELETE                                              | yes       |
| Top-level UNION / INTERSECT / EXCEPT                | yes       |
| Top-level VALUES                                    | yes       |
| MERGE                                               | not yet   |
| GROUPING SETS / CUBE / ROLLUP                       | not yet   |
| TABLESAMPLE                                         | not yet   |
| OFFSET ... FETCH (SQL standard form)                | not yet   |
| Named parameters (`:foo`)                           | not yet   |

Window functions and on-conflict cover the most-asked features. MERGE, grouping sets, and dialect JSON ops are reachable through `[:raw "..."]` until first-class support lands.
