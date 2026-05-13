# Clauses

`sql/format` reads a map and emits clauses in a fixed order: `:select`, `:from`, `:where`, `:order-by`, `:limit`, `:offset`. Map insertion order is irrelevant.

## `:select`

Vector of columns. Each item is a [column expression](parameters.md#identifiers).

```phel
{:select [:id :name]}                ; SELECT id, name
{:select [[:id :user_id]]}           ; SELECT id AS user_id
{:select [:users/id]}                ; SELECT users.id
{:select ["*"]}                       ; SELECT *
```

## `:from`

Vector of tables. Each item is a [table expression](parameters.md#identifiers).

```phel
{:from [:users]}                     ; FROM users
{:from [:users :orders]}              ; FROM users, orders
{:from [[:users :u]]}                ; FROM users AS u
```

## `:where`

A single vector: `[op & args]`.

### Binary comparison

`:=`, `:!=`, `:<`, `:>`, `:<=`, `:>=`, `:like`.

```phel
[:= :status "active"]                ; status = ?
[:>= :age 18]                        ; age >= ?
[:like :name "A%"]                   ; name LIKE ?
[:= :a :b]                            ; a = b   (both keywords -> idents)
```

### Logical

```phel
[:and [:= :a 1] [:= :b 2]]           ; (a = ?) AND (b = ?)
[:or  [:= :a 1] [:= :b 2]]           ; (a = ?) OR (b = ?)
[:not [:= :a 1]]                     ; NOT (a = ?)
```

Nestable to arbitrary depth.

### Set / range / null

```phel
[:in :id [1 2 3]]                    ; id IN (?, ?, ?)
[:between :n 1 10]                   ; n BETWEEN ? AND ?
[:is-null :deleted_at]               ; deleted_at IS NULL
[:is-not-null :deleted_at]           ; deleted_at IS NOT NULL
```

`:in` rejects empty lists. Use `[:= 1 0]` or skip the clause when the list is empty.

## `:order-by`

Vector of items.

```phel
{:order-by [:id]}                    ; ORDER BY id
{:order-by [[:id :asc]]}             ; ORDER BY id ASC
{:order-by [[:id :desc]]}            ; ORDER BY id DESC
{:order-by [[:a :asc] [:b :desc] :c]} ; ORDER BY a ASC, b DESC, c
```

## `:limit` / `:offset`

Non-negative integers.

```phel
{:limit 10 :offset 20}               ; LIMIT 10 OFFSET 20
```

Negative values, non-integers, or floats raise `InvalidArgumentException`.

## Errors

Every malformed input raises `InvalidArgumentException`:

- non-map input
- unknown top-level clause
- unknown `where` operator
- non-vector `where` body
- empty `:in` list
- bad order direction (anything other than `:asc` / `:desc`)
- negative or non-integer `:limit` / `:offset`
- unsupported identifier (e.g. a number where a column is expected)
