# Parameters and identifiers

## The rule

The compiler has one bright-line distinction:

| Phel form         | Treated as       |
|-------------------|------------------|
| keyword (`:col`)  | identifier       |
| `[base alias]`    | identifier alias |
| string (`"x"`)    | raw identifier (escape hatch) |
| anything else     | parameter (`?`)  |

That is the whole rule. It makes a clause like `[:= :a :b]` self-describing: both sides are columns. Swap one for a value (`[:= :a 1]`) and the right-hand side becomes a placeholder.

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
[:id :user_id]      ; -> id AS user_id
[:users :u]          ; -> users AS u
```

### Raw string

```phel
"COUNT(*)"          ; -> COUNT(*)
```

Use sparingly: the compiler does not validate or escape raw strings. Anything you put in a string ends up in the SQL verbatim.

## Placeholders

All values become `?` placeholders, in left-to-right emission order:

```phel
(sql/format
  {:select [:id]
   :from   [:users]
   :where  [:and [:= :status "active"] [:>= :age 18]]
   :limit  10})
;; => ["SELECT id FROM users WHERE (status = ?) AND (age >= ?) LIMIT 10" ["active" 18]]
```

The `params` vector lines up positionally with the `?` markers. Pass it straight to PDO or any driver that accepts positional bind parameters.

`:limit` and `:offset` are integers and inlined into the SQL (not bound), because most drivers reject placeholders in those positions.

## NULL

`null` is not a placeholder value. Use `[:is-null :col]` and `[:is-not-null :col]` instead. Binding `null` through `?` is allowed but rarely does what you want under SQL three-valued logic.

## Future: named parameters

Positional `?` is the MVP. Named parameters (`:foo`) will land alongside the PDO bridge library when it ships.
