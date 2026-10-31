# phel-sql docs

Turn a Phel map into a `[sql params]` tuple. Pure functions, no I/O, no database driver.

## Start here

- **[Quickstart](quickstart.md)**: three queries, end to end.
- **[Clauses](clauses.md)**: every clause, every operator, every error.
- **[Expressions](expressions.md)**: tagged forms (`:fn`, `:cast`, `:case`, `:over`, `:filter`, `:lateral`, `:raw`) and arithmetic and JSON operators.
- **[Parameters](parameters.md)**: identifier rules, values, subqueries, raw fragments.
- **[Cookbook](cookbook.md)**: dynamic filters, pagination, upsert, CTE, window ranking, running a query with PDO, FAQ.
- **[Contributing](contributing.md)**: repo layout, tests, how to add a clause or operator.

## Mental model

```
phel map  ->  sql/format  ->  [sql-string, params-vector]
```

`sql/format` does only that. It does not run queries, connect to a database, quote identifiers or know your SQL dialect.

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
| Arithmetic and Postgres JSON operators              | yes       |
| WHERE: 21 operators incl. EXISTS, NOT IN, ILIKE     | yes       |
| GROUP BY, HAVING                                    | yes       |
| ORDER BY (ASC / DESC / NULLS FIRST / NULLS LAST)    | yes       |
| LIMIT, OFFSET                                       | yes       |
| FOR UPDATE / SHARE (+ NOWAIT / SKIP LOCKED)         | yes       |
| RETURNING                                           | yes       |
| Window functions (`:over` + frame DSL)              | yes       |
| WINDOW clause and named windows (`:window`)        | yes       |
| FILTER (WHERE ...)                                  | yes       |
| Function calls (`:fn`)                              | yes       |
| CAST                                                | yes       |
| CASE expressions                                    | yes       |
| Subqueries anywhere                                 | yes       |
| Raw SQL escape hatch `[:raw "..."]`                 | yes       |
| INSERT (vector rows or map rows)                    | yes       |
| INSERT ... SELECT (`:query`)                        | yes       |
| INSERT ON CONFLICT (Postgres)                       | yes       |
| INSERT ON DUPLICATE KEY UPDATE (MySQL)              | yes       |
| UPDATE                                              | yes       |
| DELETE                                              | yes       |
| Top-level UNION / INTERSECT / EXCEPT                | yes       |
| Top-level VALUES                                    | yes       |
| GROUP BY ROLLUP / CUBE / GROUPING SETS              | yes       |
| MERGE                                               | not yet   |
| TABLESAMPLE                                         | not yet   |
| OFFSET ... FETCH (SQL standard form)                | not yet   |
| Named parameters (`:foo`)                           | not yet   |

Use `[:raw "..."]` for MERGE and dialect-specific JSON operators until they are supported.
