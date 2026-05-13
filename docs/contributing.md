# Contributing

## Layout

```
src/sql.phel             ; public API: sql/format + private emitters
tests/sql-test.phel      ; one deftest per clause and per error path
docs/                    ; this directory
phel-config.php          ; flat layout, library
composer.json
```

That is the whole repo. Resist adding directories until they earn their keep.

## Run tests

```bash
composer install
composer test
```

CI runs the same on PHP 8.4 and 8.5.

## REPL

```bash
composer repl
```

```phel
phel:1> (require phel.sql :as sql)
phel:2> (sql/format {:select [:id] :from [:users]})
```

## Architecture

Two layers:

1. **Expressions** (`ident+`, `operand`, `emit-tagged`) handle anything that lives inside a clause: identifiers, values, tagged forms (`:fn`, `:cast`, `:case`, `:over`, `:filter`, `:lateral`, `:raw`), and subqueries. They all return `[sql params]` tuples.
2. **Clauses** are emitter functions registered in dispatch maps:

```
clause keyword  -->  emitter function  -->  [sql params]
```

Per-statement dispatch maps and `*-order` vectors:

```
select-emitters / select-order
update-emitters / update-order
delete-emitters / delete-order
```

INSERT and set-ops are hand-assembled (they have stricter shape constraints). Statement kind is auto-detected by `statement-type` from the keys present.

Subqueries work because `compile-query` is called recursively from `ident+`, `operand`, the join helper, the WITH helper, and the LATERAL emitter. Anywhere a clause builds a query string, it also pulls params through.

## Add a new clause to SELECT

1. Write a private emitter in `src/sql.phel`:

   ```phel
   (defn- emit-window [windows]
     (let [[s p] (idents+ windows)]
       [(str "WINDOW " s) p]))
   ```

   An emitter takes the clause's value and returns `[sql params]`.

2. Register it:

   ```phel
   (def- select-emitters
     {;; ...
      :window emit-window})

   (def- select-order
     [;; ... order matters
      :window
      :order-by :limit :offset
      :for :returning])
   ```

3. Add `deftest`s per accepted shape and per error it raises.

4. Update `docs/clauses.md` and the README coverage table.

## Add a new WHERE operator

1. Add a row to `binary-ops` if it is a simple binary op, or extend the `case` in `emit-where` for special shapes.
2. Add tests.
3. Update `docs/clauses.md`.

## Add a new tagged expression form

1. Pick a tag keyword and add it to `expr-tags`.
2. Write the private emitter (`emit-<thing>`) returning `[sql params]`.
3. Add a branch in `emit-tagged`.
4. Tests + `docs/expressions.md` entry.

The expression form is then automatically usable everywhere an identifier or operand is accepted, including inside other tagged forms.

## Style

- `defn-` / `def-` for everything that is not part of the public API.
- Public functions get `:doc`, `:example`, `:see-also` metadata.
- Pure functions only. No I/O, no PDO, no globals.
- Phel naming: kebab-case for symbols.
- Use threading (`->`, `->>`) and `case` for dispatch where it improves clarity.
- Conventional commits: `feat:`, `fix:`, `ref:`, `chore:`, `docs:`, `test:`, `ci:`.

## Reporting bugs

Open an issue with the input map, the expected output, and what you actually got. A failing `deftest` is the gold standard.
