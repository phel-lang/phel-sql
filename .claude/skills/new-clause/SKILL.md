---
description: Scaffold a new SELECT clause, WHERE operator, or tagged expression form end-to-end
argument-hint: "[clause|where-op|tagged] [name]"
disable-model-invocation: true
allowed-tools: "Read, Write, Edit, Glob, Grep, Bash(composer *), Bash(./vendor/bin/phel *), Bash(vendor/bin/phel *)"
---

# New Clause / Operator / Tagged Form

Walks the contributor flow from `docs/contributing.md`. Picks the right files, adds the emitter, registers it, scaffolds tests, and updates docs.

## Context

!`ls src/sql/`
!`ls tests/sql/`

## Instructions

### 1. Parse args

- `$ARGUMENTS` → `<kind> <name>`.
- `kind` ∈ {`clause`, `where-op`, `tagged`}. If missing, ask.

### 2. Branch by kind

**Clause** (adds to SELECT — adapt for UPDATE/DELETE by analogy):

1. Write `defn- emit-<name> [v]` in `src/sql/clause.phel` (or the most fitting sub-file). Must return `[sql params]`.
2. Register in `select-emitters` (in `src/sql/dispatch.phel`).
3. Insert `<keyword>` into `select-order` at the right position.
4. If cross-file fn refs are needed, add `(declare ...)` in `src/sql.phel`.
5. Create `tests/sql/<name>-test.phel` with one `deftest` per accepted shape.
6. Add a thrown-error case in `tests/sql/error-test.phel`.
7. Update README coverage table and `docs/clauses.md`.

**WHERE operator**:

1. Simple binary → add to `binary-ops` in `src/sql/where.phel`.
2. Special shape → extend the `case` in `emit-where`.
3. Add tests in `tests/sql/where-test.phel`. Error case in `tests/sql/error-test.phel`.
4. Update `docs/clauses.md`.

**Tagged expression** (`:fn`, `:cast`, `:case`, `:over`, `:filter`, `:lateral`, `:raw` family):

1. Add the tag keyword to `expr-tags` in `src/sql/expr.phel`.
2. Write `defn- emit-<name> [...]` returning `[sql params]`.
3. Add a branch in `emit-tagged`.
4. Tests in `tests/sql/expr-test.phel`. Error case in `tests/sql/error-test.phel`.
5. Update `docs/expressions.md`.

### 3. Verify

```bash
composer format
composer test
```

### 4. Changelog

Add an `### Added` entry under `## [Unreleased]` in `CHANGELOG.md`.

## Constraints

- One `deftest` per accepted shape — don't bundle.
- Assert the full `[sql params]` tuple.
- Error message must contain the offending value.
- Never hand-concatenate values into the SQL string.
