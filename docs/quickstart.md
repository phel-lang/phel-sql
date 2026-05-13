# Quickstart

## Install

```bash
composer require phel-lang/phel-sql
```

## Require it

```phel
(ns my-app.queries
  (:require phel.sql :as sql))
```

## Three queries

### 1. Filtered select with a join

```phel
(sql/format
  {:select [:u/id :u/name]
   :from   [[:users :u]]
   :join   [[:orders :o] [:= :u/id :o/user_id]]
   :where  [:= :u/status "active"]})
;; => ["SELECT u.id, u.name FROM users AS u JOIN orders AS o ON u.id = o.user_id WHERE u.status = ?"
;;     ["active"]]
```

### 2. Insert returning the new row

```phel
(sql/format
  {:insert-into :users
   :values     [{:name "alice" :email "a@x.com"}]
   :returning  [:id]})
;; => ["INSERT INTO users (email, name) VALUES (?, ?) RETURNING id" ["a@x.com" "alice"]]
```

### 3. Update with returning

```phel
(sql/format
  {:update    :users
   :set       {:status "banned"}
   :where     [:= :id 42]
   :returning [:id :status]})
;; => ["UPDATE users SET status = ? WHERE id = ? RETURNING id, status" ["banned" 42]]
```

## Run it against a database

The library returns data. Execution is your call:

```php
[$sql, $params] = $compiled;          // from sql/format
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
```

## Next

- [Clauses](clauses.md) for the full reference.
- [Parameters](parameters.md) for how values, identifiers, and subqueries interact.
