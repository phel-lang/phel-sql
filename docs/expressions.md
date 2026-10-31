# Expression forms

Every form on this page is a vector that starts with a reserved tag. A tagged form works wherever an identifier or operand is accepted: `:select`, `:from`, `:group-by`, `:returning`, `:columns`, either side of a WHERE or SET comparison, inside `:in` and `:between`, in function arguments, and inside another tagged form.

Each example shows the SQL it produces. Forms nest: a `:case` inside an `:over`, a `:filter` inside a `:fn`, a `:cast` inside an `:in`.

## `[:raw "SQL"]`

Literal SQL fragment. The vector has exactly two elements and the second is a string; any other shape throws. Contributes no params. The compiler does not validate the SQL.

```phel
[:raw "NOW()"]              ; -> NOW()
[:raw "COUNT(*)"]           ; -> COUNT(*)
```

## `[:fn fname & args]`

Function call. `fname` is a keyword or a string, and each argument is an operand.

```phel
[:fn :now]                  ; -> now()
[:fn :count :*]             ; -> count(*)
[:fn :sum :amount]          ; -> sum(amount)
[:fn :coalesce :name "n/a"] ; -> coalesce(name, ?)            params: ["n/a"]
[:fn :date_trunc "month" :ts] ; -> date_trunc(?, ts)          params: ["month"]
[:fn :greatest [:fn :abs :a] [:fn :abs :b]] ; nested ok
```

## `[:cast expr type]`

`type` is a keyword or a string. A keyword renders as written, so use `:INT` or `:UUID` for upper case. A string renders verbatim, which allows types such as `"DECIMAL(10,2)"`.

```phel
[:cast :id :INT]            ; -> CAST(id AS INT)
[:cast :price "DECIMAL(10,2)"] ; -> CAST(price AS DECIMAL(10,2))
[:cast "7" :INT]            ; -> CAST(? AS INT)               params: ["7"]
```

## `[:case test then test then ... :else default]`

Each `test` is a WHERE vector. Each `then` value and the `:else` value is an operand.

```phel
[:case
  [:= :status "a"] "Active"
  [:= :status "b"] "Banned"
  :else            "Unknown"]
;; -> CASE WHEN status = ? THEN ? WHEN status = ? THEN ? ELSE ? END
;; params: ["a" "Active" "b" "Banned" "Unknown"]
```

`:else` is optional. When present it comes last, followed by exactly one value.

## `[:over expr window-spec]`

Window function. `expr` is any expression, usually `[:fn :row_number]` or `[:fn :sum :col]`. `window-spec` is a map with up to four keys: `:window`, `:partition-by`, `:order-by` and `:frame`. It can also be a keyword or string that names a window defined by the `:window` clause.

```phel
[:over [:fn :row_number] {}]
;; -> row_number() OVER ()

[:over [:fn :row_number] {:partition-by [:dept]
                          :order-by [[:salary :desc]]}]
;; -> row_number() OVER (PARTITION BY dept ORDER BY salary DESC)
```

### Named windows

A keyword or string as the second item renders `OVER name`. Define the window with the `:window` clause. Window names are not checked against the `:window` clause. A spec that extends a window cannot set `:partition-by`; the database rejects it.

```phel
{:select [[[:over [:fn :rank] :w] :r]]
 :from   [:emp]
 :window [[:w {:partition-by [:dept]}]]}
;; -> SELECT rank() OVER w AS r FROM emp WINDOW w AS (PARTITION BY dept)
```

### `:window` key

A spec map can extend a named window. The name comes first inside the parentheses.

```phel
[:over [:fn :sum :x] {:window :w :order-by [:day]}]
;; -> sum(x) OVER (w ORDER BY day)
```

### `:frame`

A vector of frame tokens, emitted in order. `[:preceding N]` and `[:following N]` need a non-negative integer.

| Token                    | SQL                  |
|--------------------------|----------------------|
| `:rows`                  | `ROWS`               |
| `:range`                 | `RANGE`              |
| `:groups`                | `GROUPS`             |
| `:between`               | `BETWEEN`            |
| `:and`                   | `AND`                |
| `:unbounded-preceding`   | `UNBOUNDED PRECEDING`|
| `:current-row`           | `CURRENT ROW`        |
| `:unbounded-following`   | `UNBOUNDED FOLLOWING`|
| `:exclude-no-others`     | `EXCLUDE NO OTHERS`  |
| `:exclude-current-row`   | `EXCLUDE CURRENT ROW`|
| `:exclude-group`         | `EXCLUDE GROUP`      |
| `:exclude-ties`          | `EXCLUDE TIES`       |
| `[:preceding N]`         | `N PRECEDING`        |
| `[:following N]`         | `N FOLLOWING`        |

Examples:

```phel
{:frame [:rows :unbounded-preceding]}
;; -> ROWS UNBOUNDED PRECEDING

{:frame [:rows :between [:preceding 1] :and [:following 1]]}
;; -> ROWS BETWEEN 1 PRECEDING AND 1 FOLLOWING

{:frame [:range :between :unbounded-preceding :and :current-row]}
;; -> RANGE BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW
```

## `[:filter expr where]`

Aggregate `FILTER (WHERE ...)`. Combines with `:over` for window aggregates.

```phel
[:filter [:fn :count :*] [:= :status "active"]]
;; -> count(*) FILTER (WHERE status = ?)        params: ["active"]

[:over [:filter [:fn :sum :amount] [:= :refunded false]]
       {:partition-by [:user_id]}]
;; -> sum(amount) FILTER (WHERE refunded = ?) OVER (PARTITION BY user_id)
;; params: [false]
```

## `[:lateral subquery]`

Marks a join or `:from` table as `LATERAL`. The argument must be a subquery map.

```phel
{:from [[:users :u]
        [[:lateral {:select [[:fn :max :id]]
                    :from   [:orders]
                    :where  [:= :user_id :u/id]}] :o]]}
;; FROM users AS u, LATERAL (SELECT max(id) FROM orders WHERE user_id = u.id) AS o
```

In a join, the lateral table is the first half of the `[table on-clause]` pair:

```phel
{:join [[[:lateral {:select [:*] :from [:t]}] :x] [:raw "TRUE"]]}
;; JOIN LATERAL (SELECT * FROM t) AS x ON TRUE
```

## Operators

Arithmetic and JSON operators are tagged forms. Operands follow the usual rules: a keyword is an identifier, any other value is bound as `?`, and a nested operator is wrapped in parentheses so precedence is explicit. The outer form is not wrapped.

```phel
{:select [[[:+ :price :tax] :total]
          [[:->> :data "name"] :n]]}
;; SELECT price + tax AS total, data ->> ? AS n
;; params: ["name"]

{:select [[:* [:+ :a :b] :c]]}
;; SELECT (a + b) * c
```

Arithmetic tags take two or more operands: `:+ :- :* :div :% :||`. `[:+ :a :b :c]` renders `a + b + c`.

JSON tags take exactly two operands (Postgres):

| Tag                | SQL  |
|--------------------|------|
| `:->`              | `->` |
| `:->>`             | `->>` |
| `:json-path`       | `#>` |
| `:json-path-text`  | `#>>` |
| `:json-contains`   | `@>` |
| `:json-contained-by` | `<@` |

The Phel reader cannot read the keywords `:/`, `:@>`, `:<@`, `:#>` and `:#>>`, so those operators have the names `:div`, `:json-contains`, `:json-contained-by`, `:json-path` and `:json-path-text`.

The operators work wherever an operand or identifier is accepted:

```phel
{:where [:= [:->> :data "name"] "x"]}
;; WHERE data ->> ? = ?
```

Operators that contain `?` (the Postgres key-exists family `?`, `?|`, `?&`) are not supported. PDO reads every `?` as a placeholder. Use `[:fn :jsonb_exists :data "key"]` instead.

Any wrong operand count throws.

## Position rules

The compiler has two contexts:

- **Identifier position**: select columns, tables, group-by, returning, columns. A string is written as is, a keyword is an identifier, a map is a subquery, and a two-element vector is `[base alias]`.
- **Operand position**: either side of a WHERE or HAVING comparison, SET values, IN and BETWEEN values, function arguments, CASE results. A string is bound as `?`, a keyword is an identifier, a map is a subquery, and any other vector throws.

A tagged form behaves the same in both positions. See [Parameters](parameters.md) for the full table.
