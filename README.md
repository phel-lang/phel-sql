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
(ns my-app\queries
  (:require phel-sql\sql :as sql))

(sql/format
  {:select   [:id :name]
   :from     [:users]
   :where    [:and [:= :status "active"] [:>= :age 18]]
   :order-by [[:name :asc]]
   :limit    10})
;; => ["SELECT id, name FROM users WHERE (status = ?) AND (age >= ?) ORDER BY name ASC LIMIT 10"
;;     ["active" 18]]
```

Pass the tuple to any PDO-like driver:

```php
$pdo->prepare($sql)->execute($params);
```

## Docs

- [Quickstart](docs/quickstart.md): three queries, end to end.
- [Clauses](docs/clauses.md): every supported clause and its shape.
- [Parameters](docs/parameters.md): how identifiers, values, and `?` placeholders interact.
- [Contributing](docs/contributing.md): repo layout, tests, adding a clause.

## Status

MVP. Supported: `select`, `from`, `where`, `order-by`, `limit`, `offset`.
Planned: `join`, `group-by`, `having`, `with`.

## License

MIT
