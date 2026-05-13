---
description: Phel conventions + test layout for phel-sql
globs: src/**,tests/**
---

# Phel Conventions

## Naming

- kebab-case for symbols: `emit-where`, `compile-query`, `binary-ops`.
- `defn-` / `def-` for private (not part of the public API).
- Namespace `phel.sql`. Sub-files in `src/sql/` start with `(in-ns phel.sql)`.
- Test namespaces: `phel-sql-tests.sql.<topic>-test`.

## Docstrings

Public functions (only `sql/format` and supporting public helpers) get:

- `:doc` — what it does, in plain prose.
- `:see-also` — related symbols as strings.
- `:example` — inline usage example matching the actual return shape.

## Comments

- `;` for line comments. `;;` for standalone, `;` for trailing.
- Use section banners (`;; ====`) sparingly — the existing ones mark forward-decl blocks and sub-file load points.

## Semantics

- Pure functions only — no I/O, no PDO, no globals.
- Every emitter returns `[sql params]`. Tuple discipline is load-bearing.
- Prefer threading (`->`, `->>`) and `case` for dispatch where it improves clarity.
- Use `defstruct` for data, not maps-of-keywords, when shape is fixed (currently not used — keep an eye out).

## Multi-file namespace

`src/sql.phel` declares cross-file symbols up-front with `(declare ...)` then `(load "sql/<file>")`s the sub-files. Adding a new cross-file fn? Update the declare block first.

## Tests (`tests/sql/`)

Layout:

```
tests/sql/<topic>-test.phel    one file per clause / concern
tests/sql/error-test.phel      every thrown error has a test
tests/e2e-test.phel            end-to-end black-box scenarios
```

Files named `<topic>-test.phel`, namespaces `phel-sql-tests.sql.<topic>-test`.

```phel
(ns phel-sql-tests.sql.<topic>-test
  (:require phel.sql :as sql)
  (:require phel.test :refer [deftest is testing]))

(deftest test-<descriptive-name>
  (is (= ["SELECT id FROM t" []]
         (sql/format {:select [:id] :from [:t]}))))
```

Rules:

- One behavior per `deftest`. Split shapes; don't bundle.
- Assert the **full `[sql params]` tuple**. Asserting only SQL lets param bugs slip.
- Every thrown error gets a case in `error-test.phel`.
- Run focused: `vendor/bin/phel test tests/sql/<topic>-test.phel`. Full: `composer test`.
