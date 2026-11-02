---
description: "Use when editing Phel source or tests in phel-sql (naming, docstrings, namespaces, test layout)."
globs: src/**,tests/**
---

# Phel conventions

## Source

- kebab-case symbols (`emit-where`, `compile-query`, `binary-ops`). `defn-` / `def-` for anything not public.
- Namespace `phel.sql`. Sub-files in `src/phel/sql/` start with `(in-ns phel.sql)`.
- Public functions (`sql/format` and supporting helpers) carry `:doc` (plain prose), `:see-also` (strings) and `:example` (matches the real return shape).
- Pure functions only: no I/O, no PDO, no globals. Every emitter returns `[sql params]`.
- Use threading (`->`, `->>`) and `case` for dispatch where clearer. Use `defstruct` for fixed-shape data (none yet).
- Comments: `;` trailing, `;;` standalone. Keep section banners (`;; ====`) to forward-declaration blocks and sub-file load points.
- `src/phel/sql.phel` declares cross-file symbols with `(declare ...)`, then `(load "sql/<file>")`s the sub-files. Add a new cross-file function to the declare block first.

## Tests

- One file per clause or concern: `tests/sql/<topic>-test.phel`, namespace `phel-sql-tests.sql.<topic>-test`. Every thrown error has a case in `tests/sql/error-test.phel`. Black-box scenarios go in `tests/e2e-test.phel`.
- One behavior per `deftest`. Split shapes; do not bundle.
- Assert the full `[sql params]` tuple. Asserting only the SQL lets parameter bugs through.
- Go through `sql/format`, not private helpers.

```phel
(ns phel-sql-tests.sql.<topic>-test
  (:require phel.sql :as sql)
  (:require phel.test :refer [deftest is testing]))

(deftest test-<descriptive-name>
  (is (= ["SELECT id FROM t" []]
         (sql/format {:select [:id] :from [:t]}))))
```
