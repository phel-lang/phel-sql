# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Cookbook with dynamic filters, pagination, upsert, CTE, window ranking, a PDO example and an FAQ; `sql/format` docstring has a multi-statement example and `:see-also` (#44)
- Operator expressions: `:+ :- :* :div :% :||` take two or more operands, and the JSON tags `:->`, `:->>`, `:json-path`, `:json-path-text`, `:json-contains` and `:json-contained-by` take exactly two. Nested operators are wrapped in parentheses (#47)
- Set operations accept `:with`, `:with-recursive`, `:order-by`, `:limit` and `:offset`; other extra keys still throw (#46)
- INSERT ... SELECT: `:query` takes a SELECT, VALUES or set operation query in place of `:values`, and works with `:with`, upserts and `:returning` (#45)
- `:window` clause for named windows, `[:over expr :name]` to use one, and a `:window` key in window specs to extend one (#50)
- `[:rollup ...]`, `[:cube ...]` and `[:grouping-sets ...]` are accepted as `:group-by` items (#49)
- `nil` items inside `:and` and `:or` are skipped, and a SELECT `:where` or `:having` that is `nil` or ends up empty is omitted, so optional filters work with `when`. An empty `:where` in UPDATE or DELETE throws (#42)

### Changed

- Columns named like an operator tag (`div`, `%`, `*`, `+`, `-`, `||`) can no longer be aliased or ordered as `[:div :d]` or `[:div :desc]`. Use a string name: `["div" :d]` (#47)
- Error messages name the clause they came from (`in :join: ...`) and suggest the closest clause, WHERE operator or expression tag, or the snake_case spelling of a kebab-case identifier. The message text changed, so callers matching on it must update. The exception class is still `InvalidArgumentException` (#43)
- A single remaining item in `:and` or `:or` renders without parentheses (`a = ?`, not `(a = ?)`). The meaning is unchanged (#42)

### Fixed

- Move sources to `src/phel/` so the compiled `out/` tree loads the sub-files. `phel build` followed by `require "out/index.php"` no longer throws `Cannot locate sql/util` (#2).
- INSERT with `:columns` and map rows binds values in `:columns` order and rejects map keys not listed in `:columns` (#8)
- INSERT rejects empty rows, ragged vector rows and rows whose width differs from `:columns` (#9)
- INSERT rejects upsert keys that have no `:on-conflict`, and `:do-update-where` without `:do-update-set`, instead of dropping them (#10)
- WHERE operators throw when given the wrong number of arguments instead of binding `nil` or dropping extras (#11)
- Clauses that list columns or tables throw a clear error for empty or non-vector values instead of emitting invalid SQL or leaking a Phel runtime message (#12)
- UPDATE without `:set` throws instead of compiling to invalid SQL (#13)
- Untagged vectors in value positions throw instead of being bound as one parameter, so a mistyped tag like `[:fnn ...]` is reported (#14)
- Error messages show offending values as Phel literals (`:bogus`, `{:a 1}`) instead of a `var_export` dump of internals (#15)
- Tagged forms, aliases and `:for` reject extra or malformed elements, and window frame offsets must be non-negative (#16)
- Clauses accept lists and lazy seqs again, string `:columns` work with map rows, an empty `:columns` reports itself, and keyword identifiers allow non-ASCII letters (#35)

### Changed

- `[:rollup x]` and `[:cube x]` no longer mean an alias outside `:group-by`; they throw. Use a string name such as `["rollup" :r]` for an alias (#49)
- `sql/format` is faster: wide map-row INSERTs about 2.2x, typical SELECTs about 18%.
- Docs list every identifier position that renders a string verbatim, including `:set` keys, function names, cast types and aliases (#17)
- The README links to the list of identifier positions that render a string verbatim (#17)
- Keyword identifiers are validated: only letters, digits, `_`, `$`, dots between parts and a bare `*` are accepted, so `(keyword user-input)` cannot inject SQL. Use a string or `[:raw ...]` for other names (#18)
- `[:= x nil]` and `[:!= x nil]` compile to `IS NULL` and `IS NOT NULL` instead of comparing with a bound NULL (#19)

## [0.2.1] - 2026-10-07

### Fixed

- Forward declarations name only the function. The next phel-lang release rejects `(declare f [x])`, which used to drop the vector.

## [0.2.0] - 2026-10-04

### Changed

- Require phel-lang ^0.54.

## [0.1.0] - 2026-05-13

### Added
- `sql/format` with MVP clause set: `SELECT`, `FROM`, `WHERE`, `GROUP BY`, `HAVING`, `ORDER BY`, `LIMIT`, `OFFSET`.
- Full clause coverage: joins, subqueries, DML (`INSERT` / `UPDATE` / `DELETE`), set operations (`UNION` / `INTERSECT` / `EXCEPT`), raw SQL passthrough.
- Window functions, `ON CONFLICT`, expression nodes, `DISTINCT ON`, `USING`, lateral joins.
- End-to-end black-box scenarios at `tests/` root.
- Documentation tree under `docs/` covering all supported clauses.

### Changed
- Split `src/sql.phel` into single-namespace multi-file layout.
- Split `tests/sql-test.phel` into per-domain files.
- Renamed namespace `phel-sql.sql` → `phel.sql`.
- Retargeted package as `phel-sql` library; trimmed CI workflow and removed CLI/Docker scaffolding.

[Unreleased]: https://github.com/phel-lang/phel-sql/compare/v0.2.1...HEAD
[0.2.1]: https://github.com/phel-lang/phel-sql/compare/v0.2.0...v0.2.1
[0.2.0]: https://github.com/phel-lang/phel-sql/compare/v0.1.0...v0.2.0
[0.1.0]: https://github.com/phel-lang/phel-sql/releases/tag/v0.1.0
