---
description: Use when changing emitters, clauses, dispatch maps or tagged forms in phel-sql.
globs: src/phel/sql/**,src/phel/sql.phel
---

# SQL DSL

Two layers (details in `docs/contributing.md`):

1. Expressions: `ident+`, `operand`, `emit-tagged` handle everything inside a clause (identifiers, values, subqueries, tagged forms `:fn`, `:cast`, `:case`, `:over`, `:filter`, `:lateral`, `:raw`). All return `[sql params]`.
2. Clauses: emitters registered in the dispatch maps of `src/phel/sql/dispatch.phel`: `select-emitters` / `select-order`, `update-emitters` / `update-order`, `delete-emitters` / `delete-order`. INSERT and set operations are hand-assembled in `src/phel/sql/dml.phel`. `statement-type` picks the kind from the clause keys present.

## Invariants

- Never concatenate a value into the SQL string; return it in `params`.
- Subqueries recurse through `ident+`, `operand` or `compile-query`. Do not reimplement recursion at the call site.
- Clause order comes from the `*-order` vectors, not the input map (`test-clause-order-is-fixed`).
- Throw on unknown shapes with the offending value. `[:raw "SQL"]` is the only escape hatch.

## Add a SELECT clause

1. Write `defn- emit-<name> [v]` in `src/phel/sql/clause.phel` (or a fitting sub-file).
2. Register it in `select-emitters` and put its keyword in `select-order` at the right position. Add a `(declare ...)` in `src/phel/sql.phel` for a new cross-file function.
3. Add `tests/sql/<name>-test.phel` (one test per accepted shape) and an error case in `tests/sql/error-test.phel`.
4. Update the README coverage table and `docs/clauses.md`.

UPDATE and DELETE work the same way with their own maps.

## Add a WHERE operator

1. Simple binary: add to `binary-ops` in `src/phel/sql/where.phel`. Special shape: extend the `case` in `emit-where` and `special-op-arity`.
2. Tests in `tests/sql/where-test.phel` and an error case in `tests/sql/error-test.phel`.
3. Update `docs/clauses.md`.

## Add a tagged expression form

1. Add the tag to `expr-tags` in `src/phel/sql/expr.phel`.
2. Write `emit-<thing>` returning `[sql params]` and add a branch in `emit-tagged`. It then works anywhere an identifier or operand is accepted, nested forms included.
3. Tests in `tests/sql/expr-test.phel` and an error case in `tests/sql/error-test.phel`.
4. Add a `docs/expressions.md` entry.
