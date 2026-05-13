# Expression forms

Every form in this page is a vector whose first element is one of the reserved
expression tags. They are accepted in any position that takes an identifier or
operand: `:select`, `:from`, `:group-by`, `:returning`, `:columns`, the LHS or
RHS of any WHERE / SET comparison, inside `:in` / `:between`, in function
arguments, and inside one another.

Each form below is followed by the SQL it produces. Tagged forms are completely
composable: put a `:case` inside an `:over`, a `:filter` inside a `:fn`, a
`:cast` inside an `:in`, and so on.

## `[:raw "SQL"]`

Literal SQL fragment. Contributes no params. The compiler does not validate it.

```phel
[:raw "NOW()"]              ; -> NOW()
[:raw "COUNT(*)"]           ; -> COUNT(*)
```

## `[:fn fname & args]`

Function call. `fname` is a keyword or string; each arg goes through the
expression pipeline.

```phel
[:fn :now]                  ; -> now()
[:fn :count :*]             ; -> count(*)
[:fn :sum :amount]          ; -> sum(amount)
[:fn :coalesce :name "n/a"] ; -> coalesce(name, ?)            params: ["n/a"]
[:fn :date_trunc "month" :ts] ; -> date_trunc(?, ts)          params: ["month"]
[:fn :greatest [:fn :abs :a] [:fn :abs :b]] ; nested ok
```

## `[:cast expr type]`

`type` is a keyword or string. Keywords render bare (use `:INT`, `:UUID`, etc.
for upper-case); strings render verbatim, allowing parameterised types like
`"DECIMAL(10,2)"`.

```phel
[:cast :id :INT]            ; -> CAST(id AS INT)
[:cast :price "DECIMAL(10,2)"] ; -> CAST(price AS DECIMAL(10,2))
[:cast "7" :INT]            ; -> CAST(? AS INT)               params: ["7"]
```

## `[:case test then test then ... :else default]`

Each `test` is a where-clause vector. Each `then` and the `:else` value are
operands.

```phel
[:case
  [:= :status "a"] "Active"
  [:= :status "b"] "Banned"
  :else            "Unknown"]
;; -> CASE WHEN status = ? THEN ? WHEN status = ? THEN ? ELSE ? END
;; params: ["a" "Active" "b" "Banned" "Unknown"]
```

`:else` is optional; omit it for `CASE ... END` without a default. `:else`
must be the final pair and must be followed by exactly one value.

## `[:over expr window-spec]`

Window function. `expr` is typically `[:fn :row_number]`, `[:fn :rank]`,
`[:fn :sum :col]`, etc. (any expression). `window-spec` is a map with up to
three keys: `:partition-by`, `:order-by`, `:frame`.

```phel
[:over [:fn :row_number] {}]
;; -> row_number() OVER ()

[:over [:fn :row_number] {:partition-by [:dept]
                          :order-by [[:salary :desc]]}]
;; -> row_number() OVER (PARTITION BY dept ORDER BY salary DESC)
```

### `:frame`

A vector of frame tokens emitted in order. Tokens:

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

Marks a join or `:from` table as `LATERAL`. The subquery argument must be a
map.

```phel
{:from [[:users :u]
        [[:lateral {:select [[:fn :max :id]]
                    :from   [:orders]
                    :where  [:= :user_id :u/id]}] :o]]}
;; FROM users AS u, LATERAL (SELECT max(id) FROM orders WHERE user_id = u.id) AS o
```

For a join, place the lateral table as the first half of the `[table on-clause]`
pair:

```phel
{:join [[[:lateral {:select [:*] :from [:t]}] :x] [:raw "TRUE"]]}
;; JOIN LATERAL (SELECT * FROM t) AS x ON TRUE
```

## Position rules

The compiler has two contexts:

- **Identifier position**: select columns, from tables, group-by, returning,
  columns. A bare string is a raw fragment; a bare keyword is an identifier; a
  bare map is a subquery; a non-tagged vector is `[base alias]`.
- **Operand position**: where/having LHS and RHS, set values, in / between
  values, function arguments, case then-values. A bare string is a parameter
  (bound as `?`); a bare keyword is an identifier; a bare map is a subquery.

Every tagged form behaves the same in both positions: it produces SQL plus the
right params, and the surrounding context handles aliasing or placeholder
placement.
