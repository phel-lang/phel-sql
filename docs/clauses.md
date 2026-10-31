# Clauses

Every clause emits SQL in a fixed order, regardless of map insertion order.

- SELECT order: `:with`, `:with-recursive`, one of (`:select` / `:select-distinct` / `:select-distinct-on`), `:from`, joins, `:where`, `:group-by`, `:having`, `:window`, `:order-by`, `:limit`, `:offset`, `:for`, `:returning`.
- UPDATE order: `:with`, `:update`, `:set`, `:from`, `:where`, `:order-by`, `:limit`, `:returning`.
- DELETE order: `:with`, `:delete-from`, `:where`, `:order-by`, `:limit`, `:returning`.
- INSERT order: `:with`, `:insert-into`, `:columns`, `:values` or `:query`, `:on-conflict` / `:on-duplicate-key-update`, `:returning`.
- Top-level VALUES: `{:values [[...] ...]}` with no other keys.
- Set ops: `{:union [...]}` (and `:union-all`, `:intersect`, `:except`) with optional `:with`, `:with-recursive`, `:order-by`, `:limit` and `:offset`.

Wherever a clause accepts an identifier or value it also accepts the [tagged expression forms](expressions.md) and subquery maps.

Clauses that list columns or tables (`:select`, `:select-distinct`, `:from`, `:group-by`, `:returning`, `:order-by`, `:columns`) take a non-empty vector or list. An empty or non-sequential value throws.

## SELECT

### `:select`

Vector of columns. Items: keyword, `[col alias]`, `[:raw "SQL"]`, `[:fn ...]`, `[:over ...]`, etc., or `[subquery alias]`.

```phel
{:select [:id :name]}
{:select [[:id :user_id]]}
{:select [:users/id]}
{:select [[[:fn :count :*] :total]]}
{:select [[[:over [:fn :row_number] {:partition-by [:dept]}] :rk]]}
```

### `:select-distinct`

Same shape as `:select`, emits `SELECT DISTINCT`.

### `:select-distinct-on` (Postgres)

A map: `{:on [cols] :select [cols]}`. Both keys required.

```phel
{:select-distinct-on {:on [:country] :select [:id :name]}
 :from [:users]
 :order-by [[:country :asc]]}
;; SELECT DISTINCT ON (country) id, name FROM users ORDER BY country ASC
```

Only one of `:select` / `:select-distinct` / `:select-distinct-on` may be present.

### `:from`

Vector of tables. Items: keyword, `[table alias]`, `[subquery alias]`, or `[[:lateral subquery] alias]`.

```phel
{:from [:users]}
{:from [[:users :u]]}
{:from [[{:select [:id] :from [:t]} :sub]]}
{:from [[:users :u]
        [[:lateral {:select [:*] :from [:logs] :where [:= :uid :u/id]}] :l]]}
```

### Joins

`:join`, `:left-join`, `:right-join`, `:full-join` take a flat vector of `[table on-clause]` pairs. The on-clause is one of:

- A where-clause vector: `[:= :a/id :b/aid]`
- A USING clause: `[:using col1 col2 ...]`
- A tagged form: `[:raw "TRUE"]`

```phel
{:join [[:orders :o] [:= :u/id :o/user_id]]}        ; JOIN orders AS o ON ...
{:join [:b [:using :id]]}                            ; JOIN b USING (id)
{:join [:b [:using :id :tenant_id]]}                 ; JOIN b USING (id, tenant_id)
{:left-join [[[:lateral {:select [:*] :from [:t]}] :l]
             [:raw "TRUE"]]}                        ; LEFT JOIN LATERAL (...) AS l ON TRUE
```

`:cross-join` takes a vector of tables (no on-clause):

```phel
{:cross-join [:a :b]}             ; CROSS JOIN a CROSS JOIN b
```

### `:where`

A single vector: `[op & args]`.

Binary: `:=`, `:!=`, `:<`, `:>`, `:<=`, `:>=`, `:like`, `:not-like`, `:ilike`, `:not-ilike`. Both sides go through the operand rules. A literal `nil` on either side of `:=` or `:!=` compiles to `IS NULL` or `IS NOT NULL`, because `a = NULL` never matches in SQL. Other operators bind `nil` as a parameter.

Logical: `:and`, `:or`, `:not`.

Set / range / null: `:in`, `:not-in`, `:between`, `:not-between`, `:is-null`, `:is-not-null`.

Subquery presence: `:exists`, `:not-exists`.

Argument counts are checked. Binary operators, `:in` and `:not-in` take 2 arguments; `:between` and `:not-between` take 3; `:is-null`, `:is-not-null`, `:not`, `:exists` and `:not-exists` take 1; `:and` and `:or` take at least 1. Any other count throws.

```phel
[:= :status "active"]
[:= :a :b]
[:= [:fn :lower :name] "alice"]
[:in :id [1 2 3]]
[:in :id {:select [:user_id] :from [:banned]}]
[:in [:fn :lower :name] ["a" "b"]]
[:between :n 1 10]
[:between [:fn :abs :n] 1 100]
[:is-null [:fn :coalesce :a :b]]
[:exists {:select ["1"] :from [:t] :where [:= :t/uid :u/id]}]
```

`:in` rejects empty lists. Pass a subquery or omit the clause when the list is empty.

#### Optional filters

`nil` items inside `:and` and `:or` are skipped, so optional filters can use `when`:

```phel
{:where [:and (when name [:= :name name]) (when age [:>= :age age])]}
;; both set  => WHERE (name = ?) AND (age >= ?)
;; only age  => WHERE age >= ?
```

- An `:or` whose items are all `nil` is dropped, which widens the result, while a `nil` inside a non-empty `:or` is skipped, which narrows it. HoneySQL behaves the same. Use `:and` for optional narrowing filters.
- One remaining item renders without parentheses, because the parentheses only separate siblings.
- A nested `:and` or `:or` whose items are all `nil` is skipped like a `nil`.
- In SELECT, a `:where` or `:having` that is `nil`, or that ends up empty, is left out of the statement.
- In UPDATE and DELETE, a `:where` that is present but `nil` or empty throws, so a filter that came out empty never touches every row by accident. Leave `:where` out to affect every row.
- Only `:and` and `:or` items and the SELECT `:where` / `:having` values may be `nil`. A `nil` or all-`nil` operand of another operator, `:not`, a join `:on`, or `:do-update-where` still throws.

### `:group-by`

Vector of columns (any identifier or tagged form).

```phel
{:group-by [:country :city]}
{:group-by [[:fn :date_trunc "month" :ts]]}
```

`[:rollup ...]`, `[:cube ...]` and `[:grouping-sets ...]` are valid only as items of `:group-by`. Items inside them are ordinary identifiers, so tagged forms and their params work. `:rollup` and `:cube` need at least one column. `:grouping-sets` takes one vector or list per set; an empty one renders `()`. A set may also be a nested `:rollup`, `:cube` or `:grouping-sets` form. Inside `:rollup` and `:cube`, a plain vector such as `[:b :c]` renders as the composite `(b, c)`.

The syntax follows Postgres and SQL Server. MySQL only supports `WITH ROLLUP`, and SQLite supports none of it.

```phel
{:group-by [[:rollup :region :city]]}                 ; GROUP BY ROLLUP (region, city)
{:group-by [:country [:cube :region :city]]}          ; GROUP BY country, CUBE (region, city)
{:group-by [[:grouping-sets [:region :city] [:region] []]]}
; GROUP BY GROUPING SETS ((region, city), (region), ())
```

### `:having`

Same shape as `:where`. Lives between `:group-by` and `:order-by`.

```phel
{:having [:> [:fn :count :*] 5]}
```

### `:window`

Vector of `[name spec]` pairs. The name is a keyword or string. Each spec is a window-spec map, as in `[:over expr spec]`, and may use `:window` to extend an earlier window. Lives between `:having` and `:order-by`. Specs are not checked against the names used in `:over`.

```phel
{:select [[[:over [:fn :rank] :w] :r]]
 :from   [:emp]
 :window [[:w {:partition-by [:dept]}]
          [:w2 {:window :w :order-by [:salary]}]]}
;; -> SELECT rank() OVER w AS r FROM emp WINDOW w AS (PARTITION BY dept), w2 AS (w ORDER BY salary)
```

### `:order-by`

Vector of items.

```phel
{:order-by [:id]}                       ; ORDER BY id
{:order-by [[:id :asc]]}                ; ORDER BY id ASC
{:order-by [[:id :desc :nulls-last]]}   ; ORDER BY id DESC NULLS LAST
{:order-by [[:n :asc :nulls-first]]}    ; ORDER BY n ASC NULLS FIRST
{:order-by [[:raw "RANDOM()"]]}          ; ORDER BY RANDOM()
{:order-by [[[:fn :length :name] :asc]]} ; ORDER BY length(name) ASC
```

The optional third element is `:nulls-first` or `:nulls-last`. It needs the direction before it.

### `:limit` / `:offset`

Non-negative integers, inlined.

### `:for`

Row lock. The mode is `:update`, `:share`, `:no-key-update` or `:key-share`. An optional second element is `:nowait` or `:skip-locked`.

```phel
{:for :update}
{:for [:update :skip-locked]}
```

### `:returning`

Vector of columns. Permitted on INSERT, UPDATE, DELETE.

## CTEs

`:with` / `:with-recursive` take a vector of `[name query]` pairs.

```phel
{:with [[:active {:select [:id] :from [:users] :where [:= :active true]}]
        [:over18 {:select [:id] :from [:users] :where [:>= :age 18]}]]
 :select [:id]
 :from   [:active]
 :join   [:over18 [:using :id]]}
```

## INSERT

### `:insert-into`

Table keyword or `[table alias]`.

### `:columns` (optional)

Explicit column list. With vector rows, every row must have exactly this many values. With map rows, the values are written in `:columns` order and a key that is not listed throws. Omit it to use the keys of the maps.

### `:values`

A vector of vectors (positional) or a vector of maps, never a mix. Rows must not be empty, and vector rows must all have the same width. Without `:columns`, map rows use the union of their keys in alphabetical order, and a row that lacks a key gets a `nil` param.

```phel
{:insert-into :users
 :columns     [:name :age]
 :values      [["alice" 30] ["bob" 25]]}

{:insert-into :users
 :values      [{:name "alice" :age 30}
               {:name "bob"   :email "b@x"}]}
```

### `:query`

Any query map, used instead of `:values`: SELECT, VALUES or a set operation. Its params come after the `:with` params and before the upsert and `:returning` params. `:values` and `:query` are mutually exclusive, and one of them is required.

The query must have `:select`, `:select-distinct` or `:select-distinct-on`, be a set operation, or be a VALUES query. A VALUES query takes vector rows only; name the columns with `:columns` on the INSERT. INSERT, UPDATE and DELETE maps are rejected.

SQLite needs a `:where` in the SELECT before `:on-conflict` (its parser reads `ON` as a join otherwise). Postgres accepts it without one.

```phel
{:insert-into :archive
 :columns     [:id :name]
 :query       {:select [:id :name] :from [:users] :where [:< :created_at "2020-01-01"]}}
;; INSERT INTO archive (id, name) SELECT id, name FROM users WHERE created_at < ?
```

### `:on-conflict` (Postgres)

Target shapes:

- `[:col]` or `[:c1 :c2 ...]`: conflict on column(s)
- `{:on-constraint :constraint-name}`: conflict on named constraint
- `[]` or omitted: no target (catches any conflict)

Then one of:

- `:do-nothing true`
- `:do-update-set <map>`, with an optional `:do-update-where <where>`

An optional `:on-conflict-where <where>` handles partial-index conflicts.

`:on-conflict-where`, `:do-nothing`, `:do-update-set` and `:do-update-where` throw without `:on-conflict`. `:do-update-where` also needs `:do-update-set`.

```phel
{:insert-into :users
 :values     [{:email "a@x"}]
 :on-conflict [:email]
 :do-nothing  true}
;; ON CONFLICT (email) DO NOTHING

{:insert-into :users
 :values      [{:email "a@x" :name "alice"}]
 :on-conflict [:email]
 :do-update-set {:name "alice"}}
;; ON CONFLICT (email) DO UPDATE SET name = ?

{:insert-into :t
 :values [{:id 1}]
 :on-conflict [:id]
 :do-update-set {:id 1}
 :do-update-where [:= :active true]}
;; ON CONFLICT (id) DO UPDATE SET id = ? WHERE active = ?

{:insert-into :t
 :values [[1]]
 :on-conflict {:on-constraint :t_pk}
 :do-nothing true}
;; ON CONFLICT ON CONSTRAINT t_pk DO NOTHING

{:insert-into :t
 :values [{:a 1 :b 2}]
 :on-conflict [:a]
 :on-conflict-where [:= :active true]
 :do-nothing true}
;; ON CONFLICT (a) WHERE active = ? DO NOTHING
```

### `:on-duplicate-key-update` (MySQL)

A map of column to value. Cannot be combined with `:on-conflict`.

```phel
{:insert-into :users
 :values [{:email "a@x" :name "alice"}]
 :on-duplicate-key-update {:name [:raw "VALUES(name)"]}}
;; INSERT ... ON DUPLICATE KEY UPDATE name = VALUES(name)
```

## UPDATE

```phel
{:update :users
 :set   {:status "active" :verified_at [:raw "NOW()"]}
 :where [:= :id 1]}
```

`:set` is required. It is a map of column to value. A keyword value copies another column, and tagged forms work as values. Columns are written in alphabetical order so the output is stable.

UPDATE accepts `:from`, `:where`, `:order-by`, `:limit`, `:returning`.

## DELETE

```phel
{:delete-from :users :where [:= :id 1] :returning [:id]}
```

## Top-level VALUES

When `:values` is the only key, the result is a standalone `VALUES` expression.

```phel
{:values [[1 "a"] [2 "b"]]}
;; => ["VALUES (?, ?), (?, ?)" [1 "a" 2 "b"]]
```

## Set operations

`:union`, `:union-all`, `:intersect` and `:except` take a vector of two or more queries. The outer map holds one of these keys, plus optionally `:with`, `:with-recursive`, `:order-by`, `:limit` and `:offset`. They come out in that order around the set operation, whatever the map order. Params follow the SQL order. Use only one of `:with` and `:with-recursive`. On Postgres, `:order-by` on a set operation accepts output column names or positions, not expressions.

```phel
{:union [{:select [:id] :from [:a]}
         {:select [:id] :from [:b]}]}
;; => ["(SELECT id FROM a) UNION (SELECT id FROM b)" []]

{:union    [{:select [:id] :from [:a]}
            {:select [:id] :from [:b]}]
 :order-by [[:id :desc]]
 :limit    20}
;; => ["(SELECT id FROM a) UNION (SELECT id FROM b) ORDER BY id DESC LIMIT 20" []]
```

## Errors

Malformed input throws `InvalidArgumentException`. The message names the problem and shows the offending value as a Phel literal, for example `Unknown where operator: :bogus`.

Messages from inside a clause start with `in :clause:`, for example `in :join: Invalid identifier: :orders/user-id. ...`. Nested subqueries build a path, outermost clause first: `in :from > :where: ...`. Messages for unknown clauses, WHERE operators and expression tags end with `(did you mean :select?)` when a keyword is close enough (edit distance 1 for names of 3 characters or fewer, else 2). An invalid identifier with a `-` also suggests the snake_case keyword when that one is valid: `(did you mean :created_at?)`. Keywords are never converted for you.

- Input that is not a map, or an unknown clause for the statement kind.
- More than one of `:select`, `:select-distinct` and `:select-distinct-on`.
- An unsupported identifier, such as a number, or a keyword with characters other than letters, digits, `_`, `$` and `.`.
- An empty or non-sequential value for a list clause.
- WHERE: an unknown operator, a wrong argument count, a body that is not a vector, an empty `:in` list, an untagged vector as an operand, a `nil` where clause outside `:and` / `:or` / `:where` / `:having` (for example `[:not nil]`), an empty `:where` in UPDATE or DELETE, a malformed clause such as `[:= nil]`, an `:and` or `:or` with no items, and `:not` over an `:and` or `:or` whose items are all `nil`.
- A bad order direction or nulls modifier, a bad lock mode, a negative or non-integer `:limit` or `:offset`.
- A malformed CTE, `:select-distinct-on` map, `[:raw ...]`, alias, `:case`, window spec, `:window` entry or name, `[:over ...]` window argument or frame token.
- `:group-by`: an empty `:rollup`, `:cube` or `:grouping-sets`, a grouping set that is not a vector, or one of these forms used outside `:group-by`.
- INSERT: missing `:insert-into`, neither `:values` nor `:query`, both of them, a `:query` that is not a map, an INSERT/UPDATE/DELETE query, a query without a select clause, a VALUES `:query` with map rows, mixed, empty or ragged rows, a row width that differs from `:columns`, a map key missing from `:columns`.
- Upsert: a key without `:on-conflict`, `:on-conflict` without `:do-nothing` or `:do-update-set`, both `:do-nothing` and `:do-update-set`, both `:on-conflict` and `:on-duplicate-key-update`.
- UPDATE without `:set`, an empty or non-map `:set` or `:on-duplicate-key-update`.
- An empty `[:using]`.
- A set operation with keys other than the wrapper clauses above (for example `:where`), both `:with` and `:with-recursive`, or fewer than two queries.
