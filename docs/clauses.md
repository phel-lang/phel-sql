# Clauses

Every clause emits SQL in a fixed order, regardless of map insertion order.

- SELECT order: `:with`, `:with-recursive`, one of (`:select` / `:select-distinct` / `:select-distinct-on`), `:from`, joins, `:where`, `:group-by`, `:having`, `:order-by`, `:limit`, `:offset`, `:for`, `:returning`.
- UPDATE order: `:with`, `:update`, `:set`, `:from`, `:where`, `:order-by`, `:limit`, `:returning`.
- DELETE order: `:with`, `:delete-from`, `:where`, `:order-by`, `:limit`, `:returning`.
- INSERT order: `:with`, `:insert-into`, `:columns`, `:values`, `:on-conflict` / `:on-duplicate-key-update`, `:returning`.
- Top-level VALUES: `{:values [[...] ...]}` with no other keys.
- Set ops: `{:union [...]}` (and `:union-all`, `:intersect`, `:except`) with no other keys.

Any place a clause accepts an identifier or value also accepts the [tagged expression forms](expressions.md) (`[:raw ...]`, `[:fn ...]`, `[:cast ...]`, `[:case ...]`, `[:over ...]`, `[:filter ...]`, `[:lateral ...]`) and bare subquery maps.

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

Binary: `:=`, `:!=`, `:<`, `:>`, `:<=`, `:>=`, `:like`, `:not-like`, `:ilike`, `:not-ilike`. Both sides go through the operand rules.

Logical: `:and`, `:or`, `:not`.

Set / range / null: `:in`, `:not-in`, `:between`, `:not-between`, `:is-null`, `:is-not-null`.

Subquery presence: `:exists`, `:not-exists`.

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

### `:group-by`

Vector of columns (any identifier or tagged form).

```phel
{:group-by [:country :city]}
{:group-by [[:fn :date_trunc "month" :ts]]}
```

### `:having`

Same shape as `:where`. Lives between `:group-by` and `:order-by`.

```phel
{:having [:> [:fn :count :*] 5]}
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

The optional third element is `:nulls-first` or `:nulls-last`; it requires the direction to be present.

### `:limit` / `:offset`

Non-negative integers, inlined.

### `:for`

Lock hint. Mode: `:update`, `:share`, `:no-key-update`, `:key-share`. Optional second element: `:nowait` or `:skip-locked`.

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

Explicit column list for positional vector rows. Omit when rows are maps.

### `:values`

Either a vector of vectors (positional) or a vector of maps. With maps, columns are the union of keys, alphabetised for determinism, missing keys producing `nil` params.

```phel
{:insert-into :users
 :columns     [:name :age]
 :values      [["alice" 30] ["bob" 25]]}

{:insert-into :users
 :values      [{:name "alice" :age 30}
               {:name "bob"   :email "b@x"}]}
```

### `:on-conflict` (Postgres)

Target shapes:

- `[:col]` or `[:c1 :c2 ...]`: conflict on column(s)
- `{:on-constraint :constraint-name}`: conflict on named constraint
- `[]` or omitted: no target (catches any conflict)

Then one of:

- `:do-nothing true`
- `:do-update-set <map>` (with optional `:do-update-where <where>`)

Optional `:on-conflict-where <where>` for partial-index conflicts.

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

`:set` is a map of column to value. Keyword values become column copies. Raw / tagged forms work. The map is sorted alphabetically for deterministic output.

UPDATE accepts `:from`, `:where`, `:order-by`, `:limit`, `:returning`.

## DELETE

```phel
{:delete-from :users :where [:= :id 1] :returning [:id]}
```

## Top-level VALUES

When `:values` is the only relevant key (no select/from/dml keys), the result is a standalone `VALUES` expression.

```phel
{:values [[1 "a"] [2 "b"]]}
;; => ["VALUES (?, ?), (?, ?)" [1 "a" 2 "b"]]
```

## Set operations

`{:union [...]}`, `:union-all`, `:intersect`, `:except`. Value is a vector of two or more SELECT maps. The outer map must contain only that key.

```phel
{:union [{:select [:id] :from [:a]}
         {:select [:id] :from [:b]}]}
;; => ["(SELECT id FROM a) UNION (SELECT id FROM b)" []]
```

## Errors

Every malformed input raises `InvalidArgumentException`. Highlights:

- non-map input
- unknown clause for the detected statement kind
- two of `:select` / `:select-distinct` / `:select-distinct-on` together
- unknown WHERE operator or non-vector WHERE body
- empty `:in` list (without subquery)
- bad order direction / nulls modifier
- negative or non-integer `:limit` / `:offset`
- unsupported identifier (e.g. a number)
- bad lock mode / lock spec
- malformed CTE / `:select-distinct-on` map
- INSERT missing `:insert-into` or `:values`
- mixed-shape `:values`
- empty / non-map `:set`
- ON CONFLICT without `:do-nothing` or `:do-update-set`
- both `:do-nothing` and `:do-update-set`
- both `:on-conflict` and `:on-duplicate-key-update`
- empty `:on-duplicate-key-update`
- empty `[:using]`
- unknown frame token in `:over`
- empty / odd-pair `:case` / misplaced `:else`
- set-op with extras or fewer than two queries
