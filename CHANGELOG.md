# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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

[Unreleased]: https://github.com/phel-lang/phel-sql/commits/main
