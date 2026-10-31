# Cookbook

Recipes for common queries. Every query on this page is a test in `tests/e2e-test.phel` (names start with `test-cookbook-`), so the output shown is the real output.

All examples assume:

```phel
(ns my-app.queries
  (:require phel.sql :as sql))
```

## Dynamic filters

`nil` items inside `:and` and `:or` are skipped. Build each condition with `when`, and only the filters you have end up in the query.

```phel
(defn search-users [{:keys [status min-age name]}]
  {:select [:id :name]
   :from   [:users]
   :where  [:and
            (when status [:= :status status])
            (when min-age [:>= :age min-age])
            (when name [:ilike :name (str "%" name "%")])]})

(sql/format (search-users {:status "active" :min-age 18 :name "ann"}))
;; => ["SELECT id, name FROM users WHERE (status = ?) AND (age >= ?) AND (name ILIKE ?)"
;;     ["active" 18 "%ann%"]]

(sql/format (search-users {:status "active"}))
;; => ["SELECT id, name FROM users WHERE status = ?" ["active"]]

(sql/format (search-users {}))
;; => ["SELECT id, name FROM users" []]
```

When every item is `nil`, the `:where` clause is left out.

## Pagination

Order by a unique column last so pages do not overlap. `:limit` and `:offset` must be non-negative integers.

```phel
(sql/format
  {:select   [:id :name]
   :from     [:users]
   :where    [:= :status "active"]
   :order-by [[:created_at :desc] [:id :desc]]
   :limit    20
   :offset   40})
;; => ["SELECT id, name FROM users WHERE status = ? ORDER BY created_at DESC, id DESC LIMIT 20 OFFSET 40"
;;     ["active"]]
```

## Upsert

`:on-conflict` is Postgres and SQLite syntax. For MySQL use `:on-duplicate-key-update`.

```phel
(sql/format
  {:insert-into   :users
   :values        [{:email "a@x.com" :name "alice"}]
   :on-conflict   [:email]
   :do-update-set {:name "alice"}})
;; => ["INSERT INTO users (email, name) VALUES (?, ?) ON CONFLICT (email) DO UPDATE SET name = ?"
;;     ["a@x.com" "alice" "alice"]]
```

## Bulk insert

One statement, many rows. Name the columns once and pass vector rows.

```phel
(sql/format
  {:insert-into :users
   :columns     [:email :name]
   :values      [["a@x.com" "alice"] ["b@x.com" "bob"]]})
;; => ["INSERT INTO users (email, name) VALUES (?, ?), (?, ?)"
;;     ["a@x.com" "alice" "b@x.com" "bob"]]
```

## INSERT ... SELECT

Use `:query` in place of `:values` to copy rows between tables.

```phel
(sql/format
  {:insert-into :archive
   :columns     [:id :name]
   :query       {:select [:id :name]
                 :from   [:users]
                 :where  [:< :created_at "2020-01-01"]}})
;; => ["INSERT INTO archive (id, name) SELECT id, name FROM users WHERE created_at < ?"
;;     ["2020-01-01"]]
```

## CTE

Each `:with` entry is a name and a query map. Later clauses use the name like a table.

```phel
(sql/format
  {:with   [[:active {:select [:id] :from [:users] :where [:= :status "active"]}]]
   :select [:o/id :o/total]
   :from   [[:orders :o]]
   :join   [[:active :a] [:= :o/user_id :a/id]]})
;; => ["WITH active AS (SELECT id FROM users WHERE status = ?) SELECT o.id, o.total FROM orders AS o JOIN active AS a ON o.user_id = a.id"
;;     ["active"]]
```

## Window ranking

Rank rows inside each group with `[:over ...]`.

```phel
(sql/format
  {:select [:id :dept :salary
            [[:over [:fn :rank] {:partition-by [:dept]
                                 :order-by     [[:salary :desc]]}]
             :rk]]
   :from   [:employees]})
;; => ["SELECT id, dept, salary, rank() OVER (PARTITION BY dept ORDER BY salary DESC) AS rk FROM employees"
;;     []]
```

To reuse one window, name it with `:window` and refer to it by name:

```phel
(sql/format
  {:select [:id [[:over [:fn :rank] :w] :rk]]
   :from   [:employees]
   :window [[:w {:partition-by [:dept] :order-by [[:salary :desc]]}]]})
;; => ["SELECT id, rank() OVER w AS rk FROM employees WINDOW w AS (PARTITION BY dept ORDER BY salary DESC)"
;;     []]
```

## Reserved-word columns

Keywords are written as is, and `order` is a reserved word in SQL. Write the quotes yourself in a string. A string in an identifier position is copied into the SQL without checks.

```phel
(sql/format
  {:select   ["\"user\"" "\"order\"" :id]
   :from     ["\"group\""]
   :where    [:= [:raw "\"order\""] 1]
   :order-by [["\"order\"" :desc]]})
;; => ["SELECT \"user\", \"order\", id FROM \"group\" WHERE \"order\" = ? ORDER BY \"order\" DESC"
;;     [1]]
```

In `:where`, a plain string is a value, so it is bound as `?`. Wrap the quoted name in `[:raw ...]` there:

```phel
(sql/format {:select [:id] :from [:t] :where [:= "\"order\"" 1]})
;; => ["SELECT id FROM t WHERE ? = ?" ["\"order\"" 1]]
```

Quote only names you wrote in code. Never build them from user input.

## JSON parameter

Encode the value in PHP and bind the string. The driver or the column type does the rest.

```phel
(sql/format
  {:insert-into :events
   :columns     [:kind :payload]
   :values      [["signup" (php/json_encode (php-associative-array "plan" "pro"))]]})
;; => ["INSERT INTO events (kind, payload) VALUES (?, ?)"
;;     ["signup" "{\"plan\":\"pro\"}"]]
```

## Run a query with PDO

`sql/format` returns `[sql params]`. Prepare the SQL, execute it with the params and fetch the rows. This example uses an in-memory SQLite database.

```phel
(ns my-app.run
  (:require phel.sql :as sql))

(defn run [pdo query]
  (let [[statement params] (sql/format query)
        stmt               (.prepare pdo statement)]
    (.execute stmt (to-php-array params))
    (.fetchAll stmt \PDO/FETCH_ASSOC)))

(let [pdo (new \PDO "sqlite::memory:")]
  (.exec pdo "CREATE TABLE users (id INTEGER, name TEXT, status TEXT)")
  (.exec pdo "INSERT INTO users VALUES (1, 'ann', 'active'), (2, 'bob', 'off')")
  (run pdo {:select [:id :name]
            :from   [:users]
            :where  [:= :status "active"]}))
;; => [{"id" 1, "name" "ann"}] (a PHP array of rows)
```

Notes:

- `to-php-array` turns the params vector into the PHP array that `execute` expects.
- `.method` calls an instance method, `(new \Class args)` builds an object and `\Class/CONSTANT` reads a class constant.
- For INSERT, UPDATE and DELETE, skip `fetchAll`. Read `(.rowCount stmt)` instead.
- With `:returning`, fetch the rows as for a SELECT.

## FAQ

### Why only `?` placeholders?

Every driver supports positional `?`, and the params vector keeps the order in one place. Named parameters (`:foo`) are not supported yet.

### How do I quote identifiers?

The library does not quote anything, because quoting differs per database (`"x"` in Postgres and SQLite, backticks in MySQL). Keyword names are checked and written as they are. When a name is a reserved word or has odd characters, write it as a string with your own quotes. See [Reserved-word columns](#reserved-word-columns).

### Why does a kebab-case keyword fail?

SQL names use underscores, and the library does not guess a conversion. `:created-at` throws, so a typo cannot reach the database:

```phel
(sql/format {:select [:id] :from [:users] :order-by [[:created-at :desc]]})
;; throws InvalidArgumentException:
;; Invalid identifier: :created-at. Use a string or [:raw ...] for names outside letters, digits, _ and $
```

Write `:created_at`. If your columns really contain a dash, write the quoted name as a string.

### How do I move a raw SQL string over piece by piece?

Start with the parts that are easy to model and keep the rest as strings or `[:raw ...]`. Values go first, because binding them is the safety win.

```phel
;; Step 1: names as strings, one raw function call, the value bound.
(sql/format
  {:select ["id, name"]
   :from   ["users"]
   :where  [:= [:raw "lower(email)"] "a@x.com"]})
;; => ["SELECT id, name FROM users WHERE lower(email) = ?" ["a@x.com"]]

;; Step 2: everything as data.
(sql/format
  {:select [:id :name]
   :from   [:users]
   :where  [:= [:fn :lower :email] "a@x.com"]})
;; => ["SELECT id, name FROM users WHERE lower(email) = ?" ["a@x.com"]]
```

The SQL stays the same at each step, so you can compare the output with the old string after every change.

### Where can unsafe SQL get in?

Values are always bound and keyword identifiers are checked. Raw fragments and strings in identifier positions are copied as is. Treat both the same way: write them in code and never build them from user input. [Parameters](parameters.md#raw-string) lists every such position.
