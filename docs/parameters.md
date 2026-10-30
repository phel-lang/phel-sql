# Parameters, identifiers, raw SQL, and expressions

## The rule

The compiler has a small classification:

| Phel form           | In identifier position   | In value (operand) position |
|---------------------|--------------------------|-----------------------------|
| keyword (`:col`)    | identifier               | identifier                  |
| `[base alias]`      | aliased identifier       | (not used)                  |
| string (`"x"`)      | raw identifier (escape)  | **parameter `?`**           |
| `[:raw "SQL"]`      | raw fragment             | raw fragment                |
| `[:fn ...]`         | function call            | function call               |
| `[:cast ...]`       | CAST expression          | CAST expression             |
| `[:case ...]`       | CASE expression          | CASE expression             |
| `[:over ...]`       | window function          | window function             |
| `[:filter ...]`     | aggregate FILTER         | aggregate FILTER            |
| `[:lateral ...]`    | LATERAL subquery         | LATERAL subquery            |
| map                 | subquery `(...)`         | subquery `(...)`            |
| other vector        | error                    | error (cannot be bound)     |
| anything else       | error                    | parameter `?`               |

The split matters when the same form could mean two things. Values in `:where` and `:set` are bound as `?`, so a string there is a parameter. Every identifier position renders a string verbatim (see [Raw string](#raw-string)); that includes the keys of a `:set` map. To use a raw expression in `:where`, wrap it with `[:raw "..."]` (or use one of the higher-level tagged forms).

## Identifiers

### Bare keyword

```phel
:id          ; -> id
:user_id     ; -> user_id
```

A keyword may contain only letters, digits, `_` and `$`, plus `.` between parts and a bare `*`. Anything else throws `InvalidArgumentException`, so `(keyword user-input)` cannot smuggle SQL into a query. Use a string or `[:raw "..."]` for other names.

### Qualified keyword (schema / table prefix)

```phel
:users/id    ; -> users.id
```

The keyword's namespace becomes the prefix.

### Alias

```phel
[:id :user_id]               ; -> id AS user_id
[:users :u]                  ; -> users AS u
[[:raw "COUNT(*)"] :total]   ; -> COUNT(*) AS total
[[:fn :count :*] :total]     ; -> count(*) AS total
[{:select [:id] :from [:t]} :sub]
;; -> (SELECT id FROM t) AS sub
```

### Raw string

A string in any identifier position is rendered verbatim, with no escaping or validation. It has the same effect as `[:raw "..."]`.

```phel
{:select ["*"]}                       ; SELECT *
{:select ["COUNT(*) AS total"]}       ; SELECT COUNT(*) AS total
```

Identifier positions that take a verbatim string:

| Position                                             | Example                                   |
|------------------------------------------------------|-------------------------------------------|
| `:select`, `:select-distinct`, `:select-distinct-on` | `{:select ["*"]}`                         |
| `:from`, `:update`, `:delete-from`, `:insert-into`   | `{:from ["users u"]}`                     |
| join tables and `[:using ...]` columns               | `{:join ["orders o" [:= :o.user_id :u.id]]}` |
| `:group-by`, `:returning`, `:columns`                | `{:columns ["a" "b"]}`                    |
| `:order-by` and `:partition-by` columns              | `{:order-by ["created_at"]}`              |
| alias name in `[base alias]`                         | `[:id "user_id"]`                         |
| `:set` and `:on-duplicate-key-update` map keys       | `{:set {"count" 1}}` (the value is bound) |
| `:with` and `:with-recursive` names                  | `{:with [["recent" {:select [:1]}]]}`     |
| `:on-conflict {:on-constraint ...}`                  | `{:on-constraint "users_pkey"}`           |
| `[:fn name ...]` function name                       | `[:fn "date_trunc" "day" :ts]`            |
| `[:cast expr type]` type                             | `[:cast :x "numeric(10, 2)"]`             |

Never build one of these strings from user input. `[:raw ...]` carries the same warning, see [Raw SQL escape hatch](#raw-sql-escape-hatch).

## Placeholders

All values become positional `?` markers, in left-to-right emission order:

```phel
(sql/format
  {:select [:id]
   :from   [:users]
   :where  [:and [:= :status "active"] [:>= :age 18]]
   :limit  10})
;; => ["SELECT id FROM users WHERE (status = ?) AND (age >= ?) LIMIT 10" ["active" 18]]
```

`:limit` and `:offset` are integers and inlined into the SQL, because most drivers reject placeholders in those positions.

## Subqueries

Any place that accepts an identifier or operand also accepts a map. The map is compiled recursively and wrapped in parentheses; its parameters slot in at the right position.

```phel
(sql/format
  {:select [:id]
   :from   [:t]
   :where  [:in :id {:select [:user_id]
                     :from   [:banned]
                     :where  [:= :reason "spam"]}]})
;; => ["SELECT id FROM t WHERE id IN (SELECT user_id FROM banned WHERE reason = ?)"
;;     ["spam"]]
```

## NULL

`null` is not a placeholder value. Use `[:is-null :col]` and `[:is-not-null :col]` instead. Binding `null` through `?` is allowed but rarely does what you want under SQL three-valued logic.

## Tagged expression forms

Six forms (plus `[:raw "..."]`) cover everything from function calls to window expressions. They compose freely: an `:over` can wrap a `:filter` that wraps a `:fn`, a `:case` can sit inside an `:in`, a `:cast` can sit inside a `:fn` argument. See [expressions.md](expressions.md) for the full reference.

Quick sketch:

```phel
[:fn :count :*]
[:cast :id :INT]
[:case [:= :s "a"] "Active" :else "Other"]
[:over [:fn :row_number] {:partition-by [:dept] :order-by [[:salary :desc]]}]
[:filter [:fn :count :*] [:= :status "active"]]
[:lateral {:select [:*] :from [:t]}]
```

## Raw SQL escape hatch

`[:raw "SQL FRAGMENT"]` injects literal SQL anywhere the compiler accepts an identifier or operand. It contributes no parameters. Use it for things the DSL does not yet model:

```phel
{:select [[[:raw "EXTRACT(YEAR FROM created_at)"] :year]]
 :from   [:events]
 :where  [:> [:raw "JSON_EXTRACT(data, '$.score')"] 100]
 :order-by [[:raw "RANDOM()"]]}
```

A raw fragment is unvalidated. If you put untrusted data in it, you write a SQL injection. Bind values with `?` instead.

## Future: named parameters

Positional `?` is the MVP. Named parameters (`:foo`) will land alongside the PDO bridge library when it ships.
