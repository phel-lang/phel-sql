# phel-sql

Data-driven SQL DSL for [Phel](https://phel-lang.org/). Pure Phel: a map in, `[sql params]` out. No I/O, no database driver, no PHP source, no PHPUnit.

## Layout

```
src/phel/sql.phel        public API, cross-file declarations, sub-file loader
src/phel/sql/            emitters by concern (clause, where, join, dml, expr, dispatch, ...)
tests/sql/<topic>-test.phel   deftests per clause and error path
tests/e2e-test.phel      black-box scenarios
docs/                    user docs; docs/contributing.md has the contributor flow
release.sh               CHANGELOG, tag, GitHub release
phel-config.php          flat layout, library, namespace `phel.sql`
```

Emitter design, dispatch maps and the steps to add a clause, WHERE operator or tagged form are in `.agnostic-ai/rules/sql-dsl.md`. Naming, docstrings and test conventions are in `.agnostic-ai/rules/phel.md`.

## Commands

- `composer test`: `vendor/bin/phel test`, every deftest under `tests/`.
- `vendor/bin/phel test tests/sql/<topic>-test.phel`: one file, while working.
- `composer format`: format Phel source.
- `composer repl`: interactive REPL.

## Git

- Conventional commits: `feat:`, `fix:`, `ref:`, `chore:`, `docs:`, `test:`, `ci:`. No AI mention.
- Branch prefixes: `feat/`, `fix/`, `ref/`, `docs/`.
- `feat:` and `fix:` update `## [Unreleased]` in `CHANGELOG.md`.
- PRs: follow the headers of `.github/PULL_REQUEST_TEMPLATE.md`. Assign `@me`. Label one of `bug`, `enhancement`, `refactoring`, `documentation`, `pure testing`, `dependencies`.

## Gotchas

- Sources stay under `src/phel/`, so compiled output mirrors the namespace path. Moving them breaks `(load ...)` in built code.
- Every error is an `InvalidArgumentException` raised with `bad-arg`, with the offending value (`show`). Never drop unknown input silently.
- `thrown-with-msg?` in `phel.test` compares the message for equality, not by regex.
- Generated files (`CLAUDE.md`, `AGENTS.md`, `.claude/`, `.codex/`, `.agents/`) are ignored. Edit `.agnostic-ai/`, then run `agnostic-ai sync`.
