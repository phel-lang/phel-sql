# phel-sql

Data-driven SQL DSL for [Phel Lang](https://phel-lang.org/). Map in, `[sql params]` out. No database driver: the only job here is turning data into a parameterised query string.

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

Requires PHP 8.4+ and `phel-lang/phel-lang` 0.37+.

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

Pass the tuple to any PDO-like driver:

```php
$pdo->prepare($sql)->execute($params);
```

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

Subqueries (maps) work anywhere an identifier or value is accepted. `[:raw "SQL"]` is the escape hatch for fragments the DSL does not yet cover.

## Docs

- [Quickstart](docs/quickstart.md): three queries, end to end.
- [Clauses](docs/clauses.md): every clause and operator.
- [Expressions](docs/expressions.md): function calls, CAST, CASE, window, FILTER, LATERAL.
- [Parameters](docs/parameters.md): keywords vs values, subqueries, raw fragments.
- [Contributing](docs/contributing.md): repo layout, tests, adding a clause.
