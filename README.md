# phel-sql

Data-driven SQL for [Phel](https://phel-lang.org/). You pass a map and get back `[sql params]`. It never connects to a database: pass the result to PDO or any other driver.

Inspired by [HoneySQL](https://github.com/seancorfield/honeysql).

```phel
(sql/format
  {:select [:id :name]
   :from   [:users]
   :where  [:= :status "active"]})
;; => ["SELECT id, name FROM users WHERE status = ?" ["active"]]
```

## Install

```bash
composer require phel-lang/phel-sql
```

Requires PHP 8.5+ and `phel-lang/phel-lang` 0.54+.

## Use it

```phel
(ns my-app.queries
  (:require phel.sql :as sql))

(sql/format
  {:with     [[:active {:select [:id] :from [:users] :where [:= :status "active"]}]]
   :select   [:u/id
              [[:over [:fn :row_number]
                {:partition-by [:u/dept]
                 :order-by [[:u/salary :desc]]}]
               :rk]
              [[:case [:> :u/salary 100000] "high" :else "low"] :tier]]
   :from     [[:users :u]]
   :join     [[:active :a] [:using :id]]
   :where    [:>= :u/age 18]
   :order-by [[:u/dept :asc :nulls-last]]
   :limit    10})
```

Run the result with any PDO-like driver:

```php
$pdo->prepare($sql)->execute($params);
```

## Safety

- **Values are always bound.** Every value becomes a `?` and lands in the params vector. Only `:limit` and `:offset` are written into the SQL, and only as non-negative integers.
- **Keyword identifiers are checked.** `:users/id` and `:t.col` pass. A keyword with spaces, quotes or semicolons throws, so `(keyword user-input)` cannot inject SQL.
- **Strings and `[:raw "..."]` are not checked.** A string in an identifier position, and every raw fragment, is written as is. Never build one from user input. See [Parameters](docs/parameters.md#raw-string) for the full list of positions.
- **Bad input throws.** Unknown clauses, wrong argument counts, empty lists and malformed rows raise `InvalidArgumentException` with the offending value in the message.

## What it covers

| Statement | Clauses                                                                                                                            |
|-----------|------------------------------------------------------------------------------------------------------------------------------------|
| `SELECT`  | with, with-recursive, select / select-distinct / select-distinct-on, from, joins (inner / left / right / full / cross / LATERAL), USING / ON, where, group-by, having, order-by (asc / desc / nulls-first / nulls-last), limit, offset, for (lock), returning |
| `INSERT`  | with, insert-into, columns, values (vector rows or map rows), on-conflict + do-nothing / do-update-set / on-conflict-where / do-update-where (Postgres), on-duplicate-key-update (MySQL), returning |
| `UPDATE`  | with, update, set, from, where, order-by, limit, returning                                                                         |
| `DELETE`  | with, delete-from, where, order-by, limit, returning                                                                               |
| Set ops   | union, union-all, intersect, except (top-level over a vector of queries)                                                           |
| VALUES    | top-level `{:values [...]}` as a standalone expression                                                                             |

WHERE operators: `=`, `!=`, `<`, `>`, `<=`, `>=`, `like`, `not-like`, `ilike`, `not-ilike`, `and`, `or`, `not`, `in`, `not-in`, `between`, `not-between`, `is-null`, `is-not-null`, `exists`, `not-exists`.

Tagged expression forms usable anywhere an identifier or operand is accepted:

| Form                               | Renders                                  |
|------------------------------------|------------------------------------------|
| `[:raw "SQL"]`                     | raw fragment                              |
| `[:fn name & args]`                | `name(args...)`                          |
| `[:cast expr type]`                | `CAST(expr AS TYPE)`                     |
| `[:case test then ... :else d]`    | `CASE WHEN ... THEN ... ELSE ... END`    |
| `[:over expr spec]`                | window function with `:partition-by`, `:order-by`, `:frame` |
| `[:filter expr where]`             | aggregate `FILTER (WHERE ...)`           |
| `[:lateral subquery]`              | `LATERAL (...)`                          |

A map works as a subquery anywhere an identifier or value is accepted. Use `[:raw "SQL"]` for anything the DSL does not model yet.

## Docs

- [Quickstart](docs/quickstart.md): three queries, end to end.
- [Cookbook](docs/cookbook.md): recipes, running queries with PDO, FAQ.
- [Clauses](docs/clauses.md): every clause and operator.
- [Expressions](docs/expressions.md): function calls, CAST, CASE, window, FILTER, LATERAL.
- [Parameters](docs/parameters.md): identifiers, values, subqueries, raw fragments.
- [Contributing](docs/contributing.md): repo layout, tests, adding a clause.
- [Changelog](CHANGELOG.md)

## Development

```bash
composer install
composer test      # every deftest under tests/
composer format    # format the Phel source
```

The library is pure Phel, so there is no PHP source and no PHPUnit. The [contributing guide](docs/contributing.md) covers adding clauses and operators.

## License

MIT. See [LICENSE](LICENSE).
