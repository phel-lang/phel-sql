# Contributing

## Layout

```
src/sql.phel             ; public API: sql/format + private emitters
tests/sql-test.phel      ; one deftest per clause + every error path
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
phel:1> (require phel-sql.sql :as sql)
phel:2> (sql/format {:select [:id] :from [:users]})
```

## Architecture

The compiler is data-driven and open / closed:

```
clause keyword  -->  emitter function  -->  [sql params]
```

Three lookup maps drive emission: `select-emitters`, `update-emitters`, `delete-emitters`. Each comes with a `*-order` vector that fixes clause order. INSERT is hand-assembled (rows are coupled to columns) and set-ops are a separate top-level branch.

Subqueries work because `compile-query` is called recursively from `ident+`, `operand`, the join helper, and the WITH helper. Anywhere a clause builds a query string, it also pulls params through.

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

## Style

- `defn-` / `def-` for everything that is not part of the public API.
- Public functions get `:doc`, `:example`, `:see-also` metadata.
- Pure functions only. No I/O, no PDO, no globals.
- Phel naming: kebab-case for symbols.
- Use threading (`->`, `->>`) and `case` for dispatch where it improves clarity.
- Conventional commits: `feat:`, `fix:`, `ref:`, `chore:`, `docs:`, `test:`, `ci:`.

## Reporting bugs

Open an issue with the input map, the expected output, and what you actually got. A failing `deftest` is the gold standard.
