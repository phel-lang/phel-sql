# Contributing

## Layout

```
src/phel/sql.phel         public API (sql/format), forward declarations, loader
src/phel/sql/             emitters, one file per concern
tests/sql/*-test.phel     one file per clause or concern
tests/e2e-test.phel       end-to-end scenarios
docs/                     user docs, including this page
phel-config.php          flat layout, library, namespace phel.sql
release.sh               release automation
```

The sources sit under `src/phel/` because the compiled output (`out/phel/sql.php` and `out/phel/sql/*.php`) must mirror the namespace path. With any other layout the compiled `(load ...)` calls cannot find the sub-files.

Files in `src/phel/sql/`:

| File | Holds |
|------|-------|
| `util.phel` | error helper, `show`, small shared helpers |
| `ident.phel` | identifier rules, `ident`, `ident+`, `idents+` |
| `expr.phel` | `operand` and the tagged forms (`:fn`, `:cast`, `:case`, `:over`, `:filter`, `:lateral`, `:raw`) |
| `where.phel` | WHERE operators and their argument counts |
| `order.phel`, `join.phel`, `cte.phel`, `clause.phel` | the matching clauses |
| `dml.phel`, `upsert.phel` | INSERT rows, `:set`, ON CONFLICT, ON DUPLICATE KEY |
| `dispatch.phel` | per-statement emitter maps, clause order, `compile-query` |

## Set up

```bash
composer install
composer test        # every deftest under tests/
composer format      # format the Phel source
composer repl
```

For focused work run one file:

```bash
vendor/bin/phel test tests/sql/where-test.phel
```

CI runs `composer test` on PHP 8.5. Run `composer format` before you push.

```phel
phel:1> (require phel.sql :as sql)
phel:2> (sql/format {:select [:id] :from [:users]})
```

## How the compiler is built

There are two layers.

1. **Expressions** (`ident+`, `operand`, `emit-tagged`) handle anything that sits inside a clause: identifiers, values, tagged forms and subqueries. Each returns a `[sql params]` tuple.
2. **Clauses** are emitter functions registered in dispatch maps:

```
clause keyword  ->  emitter function  ->  [sql params]
```

Each statement kind has an emitter map and an order vector in `dispatch.phel`: `select-emitters` / `select-order`, `update-emitters` / `update-order`, `delete-emitters` / `delete-order`. The order vector fixes the SQL order, so the order of keys in the input map does not matter. INSERT and set operations are assembled by hand because their shapes are stricter. `statement-type` picks the kind from the keys present.

Subqueries work because `compile-query` is called from `ident+`, `operand`, the join and WITH helpers, and the LATERAL emitter. Parameters from a subquery flow back up in order.

Rules that hold everywhere:

- Every emitter returns `[sql params]`. Never write a value into the SQL string.
- Throw `InvalidArgumentException` through `bad-arg` for any input you do not recognise. Include the offending value. Never drop it silently.
- Pure functions only: no I/O, no globals.
- A helper used in another file goes into the `(declare ...)` block in `src/phel/sql.phel` first.

## Add a clause to SELECT

1. Write a private emitter in the fitting file (usually `clause.phel`). It takes the clause value and returns `[sql params]`:

   ```phel
   (defn- emit-window [windows]
     (prefixed "WINDOW" (idents+ (non-empty-vector! :window windows))))
   ```

2. Register it in `select-emitters` and put its keyword at the right place in `select-order` (`dispatch.phel`).
3. Add a `deftest` for each accepted shape and each error. Assert the full `[sql params]` tuple.
4. Update `docs/clauses.md` and the README table.

## Add a WHERE operator

1. Simple binary operator: add it to `binary-ops` in `where.phel`. Anything else: add a branch to the `case` in `emit-where` and an entry in `special-op-arity`.
2. Add tests in `where-test.phel` and an error case in `error-test.phel`.
3. Update `docs/clauses.md`.

## Add a tagged expression form

1. Add the tag to `expr-tags` in `ident.phel`.
2. Write `emit-<thing>` returning `[sql params]` and add a branch in `emit-tagged` (`expr.phel`).
3. Add tests in `expr-test.phel` and an entry in `docs/expressions.md`.

The form then works everywhere an identifier or operand is accepted, including inside other tagged forms.

## Tests

- One behavior per `deftest`. Split shapes instead of bundling them.
- Assert the whole tuple. A test that checks only the SQL lets parameter bugs through.
- Every thrown error gets a test in `error-test.phel`. Use `thrown?` for the class and `thrown-with-msg?` with the exact message when the text matters.
- Test namespaces are `phel-sql-tests.sql.<topic>-test`.

## Commits and pull requests

- Conventional commits: `feat:`, `fix:`, `ref:`, `chore:`, `docs:`, `test:`, `ci:`.
- Branch prefixes: `feat/`, `fix/`, `ref/`, `docs/`.
- `feat:` and `fix:` changes add a line under `## [Unreleased]` in `CHANGELOG.md`.
- Fill in the sections of the pull request template and pick one label.

## AI tool config

Instructions for AI coding tools live in `.agnostic-ai/` and are managed with [agnostic-ai](https://agnostic-ai.org/). The files in `.claude/`, `.codex/`, `.agents/`, `CLAUDE.md` and `AGENTS.md` are generated and ignored by Git. After cloning, run:

```bash
agnostic-ai sync
```

Edit the sources in `.agnostic-ai/`, then sync again. `agnostic-ai sync --check` fails when generated files are out of date.

## Reporting bugs

Open an issue with the input map, the output you expected and the output you got. A failing `deftest` is the best report.
