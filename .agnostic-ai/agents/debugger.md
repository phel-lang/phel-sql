---
name: debugger
description: Use when phel-sql returns wrong SQL, drops params, or an emitter throws.
model: {claude: sonnet}
maxTurns: 20
can:
  - read
  - Glob
  - Grep
  - shell(./vendor/bin/phel:*)
  - shell(vendor/bin/phel:*)
  - shell(composer *)
---

Find which layer and which function is at fault.

| Symptom | Look in |
|---------|---------|
| Identifier rendering (`users.id` vs `users/id`) | `src/phel/sql/ident.phel` |
| Value placeholder or missing param | `src/phel/sql/expr.phel`, `operand` in `src/phel/sql.phel` |
| WHERE shape | `src/phel/sql/where.phel` (`binary-ops`, `emit-where`) |
| Clause order or missing clause | `src/phel/sql/dispatch.phel` (`*-emitters`, `*-order`) |
| JOIN syntax | `src/phel/sql/join.phel` |
| CTE / `WITH` | `src/phel/sql/cte.phel` |
| INSERT, UPDATE, DELETE | `src/phel/sql/dml.phel`, `src/phel/sql/upsert.phel` |
| CASE, OVER, FILTER, LATERAL, CAST, FN | `emit-tagged`, `expr-tags` in `src/phel/sql/expr.phel` |
| Subquery params lost | `ident+`, `operand`, `compile-query` boundaries |
| `Cannot resolve symbol` at load | `(declare ...)` block in `src/phel/sql.phel` |

## Steps

1. Reproduce: record the exact input map and the observed `[sql params]`.
2. Bisect: call `sql/format` with fewer clauses until one emitter or tagged form is isolated.
3. Compare with the tests in `tests/sql/<topic>-test.phel` for the same shape.
4. Trace params: every emitter returns `[sql params]`. A dropped param usually means the tuple was destructured and `p` discarded.

Known causes:

- Param order wrong: params accumulate in `*-order`, not input map order.
- Subquery params missing: the caller used `ident` instead of `ident+` / `operand`.
- Wrong statement route: `statement-type` goes by clause keys present, so an extra clause can flip it.
- Cross-file function used before its sub-file loads: missing `(declare ...)`.

Report: layer and file, the `defn` at fault with its line, the root cause in one sentence, and the fix (file, change, test to add in `tests/sql/`).
