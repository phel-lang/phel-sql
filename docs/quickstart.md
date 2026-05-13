# Quickstart

## Install

```bash
composer require phel-lang/phel-sql
```

## Require it

```phel
(ns my-app\queries
  (:require phel-sql\sql :as sql))
```

## Three queries

### 1. Simple select

```phel
(sql/format {:select [:id :name] :from [:users]})
;; => ["SELECT id, name FROM users" []]
```

### 2. Filtered select

```phel
(sql/format
  {:select [:id]
   :from   [:users]
   :where  [:and [:= :status "active"] [:>= :age 18]]})
;; => ["SELECT id FROM users WHERE (status = ?) AND (age >= ?)" ["active" 18]]
```

### 3. Paginated select

```phel
(sql/format
  {:select   [:id :name]
   :from     [:users]
   :order-by [[:created_at :desc]]
   :limit    20
   :offset   40})
;; => ["SELECT id, name FROM users ORDER BY created_at DESC LIMIT 20 OFFSET 40" []]
```

## Run it against a database

The library returns data. Execution is your call:

```php
[$sql, $params] = $compiled;          // from sql/format
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
```

## Next

- [Clauses](clauses.md) for the full shape reference.
- [Parameters](parameters.md) for how values and identifiers are distinguished.
