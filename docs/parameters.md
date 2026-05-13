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
| anything else       | error                    | parameter `?`               |

The split matters when the same form could mean two things. Inside `:where` and `:set`, strings are values (bound as `?`). Inside `:select` and `:from`, strings are raw identifiers. To use a raw expression in `:where`, wrap it with `[:raw "..."]` (or use one of the higher-level tagged forms).

## Identifiers

### Bare keyword

```phel
:id          ; -> id
:user_id     ; -> user_id
```

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

In `:select`, `:from`, `:group-by`, `:returning`, `:columns`, a bare string is rendered verbatim. Same effect as `[:raw "..."]` but only works in identifier positions.

```phel
{:select ["*"]}                       ; SELECT *
{:select ["COUNT(*) AS total"]}       ; SELECT COUNT(*) AS total
```

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
