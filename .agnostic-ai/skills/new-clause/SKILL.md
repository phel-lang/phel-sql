---
description: Use to add a SELECT clause, WHERE operator or tagged expression form end to end.
argument-hint: "[clause|where-op|tagged] [name]"
disable-model-invocation: true
allowed-tools: "read, write, edit, Glob, Grep, shell(composer *), shell(./vendor/bin/phel *), shell(vendor/bin/phel *)"
---

# New clause, operator or tagged form

::target claude
!`ls src/phel/sql/`
!`ls tests/sql/`
::end

1. The argument is `<kind> <name>`, with kind one of `clause`, `where-op`, `tagged`. Ask if missing.
2. Follow the matching section of `.agnostic-ai/rules/sql-dsl.md` (emitter, registration, tests, docs).
3. Run `composer format` and `composer test`.
4. Add an `### Added` entry under `## [Unreleased]` in `CHANGELOG.md`.

Constraints: one `deftest` per accepted shape; assert the full `[sql params]` tuple; the error message contains the offending value; never concatenate values into the SQL string.
