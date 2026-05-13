---
description: Clause / emitter / dispatch rules for phel-sql
globs: src/sql/**,src/sql.phel
---

# SQL DSL Rules

## Architecture (two layers)

1. **Expressions** — `ident+`, `operand`, `emit-tagged` handle anything inside a clause (identifiers, values, tagged forms, subqueries). Always return `[sql params]`.
2. **Clauses** — emitter functions registered in dispatch maps in `src/sql/dispatch.phel`:
   - `select-emitters` / `select-order`
   - `update-emitters` / `update-order`
   - `delete-emitters` / `delete-order`
   INSERT and set-ops are hand-assembled in `src/sql/dml.phel` and via the dispatch entry point.

`statement-type` auto-detects the kind from clause keys present.

## Invariants

- Every emitter returns `[sql params]`. Never concatenate a value into the SQL string.
- Subqueries must recurse through `ident+` / `operand` / `compile-query`. Don't reimplement recursion at the call site.
- Clause order is fixed by `*-order` vectors. The input map order does not matter (see `test-clause-order-is-fixed`).
- The escape hatch is `[:raw "SQL"]` — never silently drop unknown shapes; throw with the offending value.

## Adding a clause to SELECT

1. Write `defn- emit-<name> [v]` in `src/sql/clause.phel` (or appropriate file).
2. Register it in `select-emitters` and place its keyword in `select-order` at the correct position.
3. Add a `tests/sql/<name>-test.phel` per accepted shape and per error.
4. Update README coverage table and `docs/clauses.md`.

## Adding a WHERE operator

1. Add to `binary-ops` if simple binary, or extend the `case` in `emit-where` for special shapes.
2. Tests in `tests/sql/where-test.phel` and an error case in `error-test.phel`.
3. Update `docs/clauses.md`.

## Adding a tagged expression form

1. Add the tag to `expr-tags`.
2. Write `emit-<thing>` returning `[sql params]`.
3. Add a branch in `emit-tagged`.
4. Tests in `tests/sql/expr-test.phel` and `docs/expressions.md` entry.
5. Now usable everywhere an identifier or operand is accepted, including nested inside other tagged forms.
