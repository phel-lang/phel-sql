# Clauses

Every clause emits SQL in a fixed order, regardless of map insertion order.

- SELECT order: `:with`, `:with-recursive`, `:select` / `:select-distinct`, `:from`, joins, `:where`, `:group-by`, `:having`, `:order-by`, `:limit`, `:offset`, `:for`, `:returning`.
- UPDATE order: `:with`, `:update`, `:set`, `:from`, `:where`, `:order-by`, `:limit`, `:returning`.
- DELETE order: `:with`, `:delete-from`, `:where`, `:order-by`, `:limit`, `:returning`.
- INSERT order: `:with`, `:insert-into`, `:columns`, `:values`, `:returning`.

## SELECT

### `:select` / `:select-distinct`

Vector of columns. Items: keyword, `[col alias]`, `[:raw "SQL"]`, or `[subquery alias]`.

```phel
{:select [:id :name]}                       ; SELECT id, name
{:select [[:id :user_id]]}                  ; SELECT id AS user_id
{:select [:users/id]}                       ; SELECT users.id
{:select [[[:raw "COUNT(*)"] :total]]}      ; SELECT COUNT(*) AS total
{:select [[{:select [:id] :from [:t]} :x]]} ; SELECT (SELECT id FROM t) AS x
{:select-distinct [:country]}               ; SELECT DISTINCT country
```

### `:from`

Vector of tables. Items: keyword, `[table alias]`, or `[subquery alias]`.

```phel
{:from [:users]}                  ; FROM users
{:from [:users :orders]}          ; FROM users, orders
{:from [[:users :u]]}             ; FROM users AS u
{:from [[{:select [:id] :from [:t]} :sub]]} ; FROM (SELECT id FROM t) AS sub
```

### Joins

`:join`, `:left-join`, `:right-join`, `:full-join` take a flat vector of `[table on-clause]` pairs:

```phel
{:join [[:orders :o] [:= :u/id :o/user_id]
        [:items :i]  [:= :o/id :i/order_id]]}
```

`:cross-join` takes a vector of tables (no ON clause):

```phel
{:cross-join [:a :b]}             ; CROSS JOIN a CROSS JOIN b
```

### `:where`

A single vector: `[op & args]`.

#### Binary comparison

`:=`, `:!=`, `:<`, `:>`, `:<=`, `:>=`, `:like`, `:not-like`, `:ilike`, `:not-ilike`.

```phel
[:= :status "active"]                ; status = ?
[:like :name "A%"]                   ; name LIKE ?
[:= :a :b]                            ; a = b   (both keywords -> idents)
[:= [:raw "LOWER(name)"] "alice"]    ; LOWER(name) = ?
[:= :id {:select [:id] :from [:t]}]  ; id = (SELECT id FROM t)
```

#### Logical

```phel
[:and [:= :a 1] [:= :b 2]]
[:or  [:= :a 1] [:= :b 2]]
[:not [:= :a 1]]
```

#### Set / range / null

```phel
[:in :id [1 2 3]]                              ; id IN (?, ?, ?)
[:in :id {:select [:user_id] :from [:banned]}] ; id IN (SELECT ...)
[:not-in :id [1 2 3]]
[:between :n 1 10]                             ; n BETWEEN ? AND ?
[:not-between :n 1 10]
[:is-null :deleted_at]
[:is-not-null :deleted_at]
```

#### Subquery presence

```phel
[:exists     {:select ["1"] :from [:t] :where [:= :t/uid :u/id]}]
[:not-exists {:select ["1"] :from [:t]}]
```

`:in` rejects empty lists; pass a subquery or omit the clause when the list is empty.

### `:group-by`

Vector of columns (same shape as `:select`).

```phel
{:group-by [:country :city]}
```

### `:having`

Same shape as `:where`. Lives between `:group-by` and `:order-by`.

```phel
{:having [:> [:raw "COUNT(*)"] 5]}
```

### `:order-by`

Vector of items.

```phel
{:order-by [:id]}                       ; ORDER BY id
{:order-by [[:id :asc]]}                ; ORDER BY id ASC
{:order-by [[:id :desc] :created_at]}   ; ORDER BY id DESC, created_at
{:order-by [[:raw "RANDOM()"]]}          ; ORDER BY RANDOM()
```

### `:limit` / `:offset`

Non-negative integers, inlined into the SQL (not bound).

```phel
{:limit 10 :offset 20}
```

### `:for`

Lock hint. Mode is `:update`, `:share`, `:no-key-update`, or `:key-share`. Optional second element: `:nowait` or `:skip-locked`.

```phel
{:for :update}                  ; FOR UPDATE
{:for :share}                   ; FOR SHARE
{:for [:update :nowait]}        ; FOR UPDATE NOWAIT
{:for [:update :skip-locked]}   ; FOR UPDATE SKIP LOCKED
```

### `:returning`

Vector of columns. Permitted on INSERT, UPDATE, DELETE.

```phel
{:returning [:id :status]}
```

## CTEs

### `:with` / `:with-recursive`

Vector of `[name query]` pairs.

```phel
{:with [[:active {:select [:id] :from [:users] :where [:= :active true]}]
        [:over18 {:select [:id] :from [:users] :where [:>= :age 18]}]]
 :select [:id]
 :from   [:active]
 :join   [:over18 [:= :active/id :over18/id]]}
```

`:with-recursive` emits `WITH RECURSIVE` instead of `WITH`.

## INSERT

### `:insert-into`

Table keyword or `[table alias]`.

### `:columns` (optional)

Explicit column list. Required when `:values` rows are vectors and the column order is significant. Omit when rows are maps (columns inferred from the union of keys, alphabetised for determinism).

### `:values`

Either a vector of vectors (positional) or a vector of maps (map-based).

```phel
;; Positional rows
{:insert-into :users
 :columns     [:name :age]
 :values      [["alice" 30] ["bob" 25]]}

;; Map rows (columns inferred)
{:insert-into :users
 :values      [{:name "alice" :age 30}
               {:name "bob"   :email "b@x"}]}
```

When map rows have different key sets, the union is taken; missing keys produce `nil` params.

## UPDATE

```phel
{:update :users
 :set   {:status "active" :verified_at [:raw "NOW()"]}
 :where [:= :id 1]}
```

`:set` is a map of column to value. Keyword values become column copies (`copy = original`). Raw fragments work. The map is sorted alphabetically for deterministic output.

UPDATE accepts `:from`, `:where`, `:order-by`, `:limit`, `:returning`.

## DELETE

```phel
{:delete-from :users :where [:= :id 1] :returning [:id]}
```

## Set operations

Top-level alternative shape. The map has exactly one of `:union`, `:union-all`, `:intersect`, `:except`, whose value is a vector of two or more SELECT maps.

```phel
{:union [{:select [:id] :from [:a]}
         {:select [:id] :from [:b]}]}
;; => ["(SELECT id FROM a) UNION (SELECT id FROM b)" []]
```

Other clauses are not permitted on the outer map: build the parts you need, then wrap them.

## Errors

Every malformed input raises `InvalidArgumentException`:

- non-map input
- unknown clause for the detected statement kind
- unknown WHERE operator
- non-vector WHERE body
- empty `:in` list (without subquery)
- bad order direction
- negative or non-integer `:limit` / `:offset`
- unsupported identifier (e.g. a number)
- bad lock mode / lock spec
- malformed CTE binding
- INSERT missing `:insert-into` or `:values`
- mixed-shape `:values` (some maps, some vectors)
- empty or non-map `:set`
- set-op shape with fewer than two queries or extra outer clauses
