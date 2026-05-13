# phel-sql

Data-driven SQL DSL for [Phel Lang](https://phel-lang.org/). Pure Phel. Map in, `[sql params]` out. No I/O, no DB driver.

## Architecture

```
src/sql.phel             public API + cross-file declarations + multi-file loader
src/sql/                 emitters split by concern (clause, where, join, dml, expr, ...)
tests/sql/*-test.phel    deftest per clause / per error path
tests/e2e-test.phel      end-to-end black-box scenarios
docs/                    user-facing docs (quickstart, clauses, expressions, parameters)
release.sh               release automation (CHANGELOG → tag → GitHub release)
phel-config.php          flat layout, library, namespace `phel.sql`
```

**Two-layer design** (see `docs/contributing.md`):

1. **Expressions** — `ident+`, `operand`, `emit-tagged` handle identifiers, values, tagged forms (`:fn`, `:cast`, `:case`, `:over`, `:filter`, `:lateral`, `:raw`), and subqueries. All return `[sql params]` tuples.
2. **Clauses** — emitter functions registered in dispatch maps:

```
clause keyword → emitter fn → [sql params]
```

Per-statement maps and order vectors live in `src/sql/dispatch.phel`:
`select-emitters` / `select-order`, `update-emitters` / `update-order`, `delete-emitters` / `delete-order`. INSERT + set-ops are hand-assembled. Statement kind auto-detected by `statement-type`.

## Testing

```bash
composer test     # vendor/bin/phel test  — runs every deftest under tests/
composer format   # vendor/bin/phel format — auto-format Phel source
composer repl     # interactive REPL
```

No PHP source. No PHPUnit. Single test runner, single formatter.

### Test scope

| Changed                  | Command                                  |
|--------------------------|------------------------------------------|
| `src/sql.phel`           | `composer test`                           |
| `src/sql/<file>.phel`    | `vendor/bin/phel test tests/sql/<file>-test.phel` for focused work |
| `tests/sql/<x>-test.phel`| `vendor/bin/phel test tests/sql/<x>-test.phel` |
| Anything user-facing     | Update `CHANGELOG.md` under `## [Unreleased]` |

## Git

- Conventional commits: `feat:`, `fix:`, `ref:`, `chore:`, `docs:`, `test:`, `ci:`. Never mention AI tooling.
- Branch prefixes: `feat/`, `fix/`, `ref/`, `docs/`.
- PRs: read `.github/PULL_REQUEST_TEMPLATE.md`, follow its headers exactly. Assign `@me`. Label from `bug`, `enhancement`, `refactoring`, `documentation`, `pure testing`, `dependencies`.
- `feat:` / `fix:` must update `## [Unreleased]` in `CHANGELOG.md`.

## Adding stuff (see `docs/contributing.md`)

- **New SELECT clause** → emitter in `src/sql/clause.phel`, register in dispatch map + order vector, tests per shape and per error, update `docs/clauses.md` + README table.
- **New WHERE op** → add to `binary-ops` or extend `case` in `emit-where`, tests, doc.
- **New tagged form** → add tag to `expr-tags`, write `emit-<thing>`, branch in `emit-tagged`, tests, `docs/expressions.md` entry.

## Style

- `defn-` / `def-` for everything not in public API. Public fns get `:doc`, `:example`, `:see-also`.
- Pure functions only. No I/O, no PDO, no globals.
- kebab-case symbols. Threading (`->`, `->>`) and `case` for dispatch when it improves clarity.
