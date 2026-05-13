# Parameters, identifiers, and raw SQL

## The rule

The compiler has a small classification:

| Phel form           | In identifier position | In value (operand) position |
|---------------------|------------------------|-----------------------------|
| keyword (`:col`)    | identifier              | identifier                 |
| `[base alias]`      | aliased identifier      | (not used)                 |
| string (`"x"`)      | raw identifier (escape) | **parameter `?`**          |
| `[:raw "SQL"]`      | raw fragment            | raw fragment               |
| map                 | subquery `(...)`        | subquery `(...)`           |
| anything else       | error                   | parameter `?`              |

The split matters when the same form could mean two things. Inside `:where` and `:set`, strings are values (bound as `?`). Inside `:select` and `:from`, strings are raw identifiers. To use a raw expression in `:where`, wrap it with `[:raw "..."]`.

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
[{:select [:id] :from [:t]} :sub]
;; -> (SELECT id FROM t) AS sub
```

### Raw string

In `:select`, `:from`, `:group-by`, `:returning`, `:columns`, a bare string is rendered verbatim. This is the same character-for-character as `[:raw "..."]`, but `[:raw ...]` is preferred for clarity and is the only thing that works in WHERE positions.

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

Any place that accepts an identifier or operand also accepts a map. The map is compiled recursively and wrapped in parentheses. Its parameters slot in at the right position:

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

## Raw SQL escape hatch

`[:raw "SQL FRAGMENT"]` injects literal SQL anywhere the compiler accepts an identifier or operand. It contributes no parameters. Use it for things the DSL does not yet model (functions, casts, vendor JSON ops, window functions):

```phel
{:select [[[:raw "EXTRACT(YEAR FROM created_at)"] :year]]
 :from   [:events]
 :where  [:> [:raw "JSON_EXTRACT(data, '$.score')"] 100]
 :order-by [[:raw "RANDOM()"]]}
```

A raw fragment is unvalidated. If you put untrusted data in it, you write a SQL injection. Bind values with `?` instead.

## Future: named parameters

Positional `?` is the MVP. Named parameters (`:foo`) will land alongside the PDO bridge library when it ships.
