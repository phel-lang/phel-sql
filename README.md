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
  (:require phel-sql.sql :as sql))

(sql/format
  {:with     [[:active {:select [:id] :from [:users] :where [:= :status "active"]}]]
   :select-distinct [:u/id :u/name]
   :from     [[:users :u]]
   :join     [[:active :a] [:= :u/id :a/id]]
   :where    [:>= :u/age 18]
   :group-by [:u/id]
   :having   [:> [:raw "COUNT(*)"] 5]
   :order-by [[:u/name :asc]]
   :limit    10
   :offset   20
   :for      [:update :skip-locked]})
```

Pass the tuple to any PDO-like driver:

```php
$pdo->prepare($sql)->execute($params);
```

## What it covers

| Statement | Clauses                                                                                                |
|-----------|--------------------------------------------------------------------------------------------------------|
| `SELECT`  | with, with-recursive, select / select-distinct, from, joins (inner / left / right / full / cross), where, group-by, having, order-by, limit, offset, for (lock), returning |
| `INSERT`  | with, insert-into, columns, values (vector rows or map rows), returning                                |
| `UPDATE`  | with, update, set, from, where, order-by, limit, returning                                             |
| `DELETE`  | with, delete-from, where, order-by, limit, returning                                                   |
| Set ops   | union, union-all, intersect, except (top-level over a vector of queries)                               |

WHERE operators: `=`, `!=`, `<`, `>`, `<=`, `>=`, `like`, `not-like`, `ilike`, `not-ilike`, `and`, `or`, `not`, `in`, `not-in`, `between`, `not-between`, `is-null`, `is-not-null`, `exists`, `not-exists`.

Subqueries (maps) work anywhere an identifier or value is accepted. `[:raw "SQL"]` is the escape hatch for fragments the DSL does not cover.

## Docs

- [Quickstart](docs/quickstart.md): three queries, end to end.
- [Clauses](docs/clauses.md): every clause and its shape.
- [Parameters](docs/parameters.md): keywords vs values, subqueries, raw fragments.
- [Contributing](docs/contributing.md): repo layout, tests, adding a clause.

## License

MIT
