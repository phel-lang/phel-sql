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
phel:1> (require phel-sql\sql :as sql)
phel:2> (sql/format {:select [:id] :from [:users]})
```

## Add a clause

The compiler is open / closed: every clause is one emitter plus one map entry.

1. Write a private emitter in `src/sql.phel`:

   ```phel
   (defn- emit-group-by [cols]
     [(str "GROUP BY " (idents cols)) []])
   ```

   An emitter takes the clause's value and returns `[sql params]`.

2. Register it:

   ```phel
   (def- clause-emitters
     {:select   emit-select
      ;; ...
      :group-by emit-group-by})

   (def- clause-order
     [:select :from :where :group-by :order-by :limit :offset])
   ```

3. Add a `deftest` per shape it accepts and one per error it raises.

4. Update `docs/clauses.md` and the README status table.

## Style

- `defn-` / `def-` for everything that is not part of the public API.
- Public functions need `:doc`, `:example`, `:see-also` metadata.
- Pure functions only. No I/O, no PDO, no globals.
- Phel naming: kebab-case for symbols.
- Conventional commits: `feat:`, `fix:`, `ref:`, `chore:`, `docs:`, `test:`, `ci:`.

## Reporting bugs

Open an issue with the input map, the expected output, and what you actually got. A failing `deftest` is the gold standard.
