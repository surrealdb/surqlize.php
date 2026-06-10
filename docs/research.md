# surqlize.php — Research (Agent 1)

Sources: `design-table-and-edge-models.md`, `docs/architecture.md`, `docs/open-questions.md`, and `surrealdb.php-refined` SDK (`RecordId`, `Surreal`, `SelectQuery`, `RelateQuery`).

---

## 1. SurrealQL examples (design doc queries, corrected)

Corrections applied per `open-questions.md`:
- `postcode` (not `zipcode`) in graph WHERE and fixtures
- `SELECT VALUE` includes `FROM user`
- `#[Schema]` typo ignored; SurrealQL unaffected
- `hasUserUnderAge` tests follow commented SurrealQL (`age > 27`), not method name
- Record IDs shown as `user:⟨id⟩` placeholders; real compile uses `RecordId::$escaped`

### 1.1 Simple model filter

**PHP (design):**
```php
User::select(['name'])
    ->where('name', Operator::EQUALS, 'beau')
    ->collect();
```

**SurrealQL:**
```sql
SELECT name FROM user WHERE name = "beau"
```

### 1.2 Edge traversal — `in()` select with WHERE

**PHP (design):** `HasAddress::…->in()->select(['name'])->where('age', '>', 27)->collect()`

`in` on `has_address` resolves to `User` (`<-` direction). `FROM` is the endpoint table, not the edge table.

**SurrealQL:**
```sql
SELECT name FROM user WHERE age > 27
```

### 1.3 Edge traversal — `selectValue`

**PHP (design):** `HasAddress::…->in()->selectValue('name')->where('age', '>', 27)->collect()`

**Design typo:** `SELECT VALUE name FROM WHERE age > 27` (missing table).

**Corrected SurrealQL:**
```sql
SELECT VALUE name FROM user WHERE age > 27
```

**Return shape:** flat list of scalars, not `User` rows. `collect()` must not hydrate as models for `selectValue`.

### 1.4 Graph select from User with nested edge WHERE + FETCH

**SurrealQL:**
```sql
SELECT name, ->has_address->address[WHERE postcode INCLUDES '24'] AS address FROM user WHERE name = "beau" FETCH address
```

Without statement-level FETCH, `address` is a record link (`address:…`), not a full object.

### 1.5 Deep / chained graph traversal

v1 compiler supports arbitrary `->` / `<-` chains via `GraphTraversal` AST.

### 1.6 Correlated edge WHERE (v2, not v1)

```sql
SELECT ->has_address[WHERE user.name = "beau"]->address[WHERE postcode INCLUDES '24'] FROM user
```

Deferred to v2 (open-questions §7). v1 only supports simple field references inside brackets.

### 1.7 Query edge record `in` / `out` with FETCH

**SurrealQL:**
```sql
SELECT in, out FROM has_address FETCH in, out
```

### 1.8 RELATE — basic

```sql
RELATE ONLY user:abc123->has_address->address:xyz789
```

### 1.9 RELATE — CONTENT

```sql
RELATE ONLY user:abc123->has_address->address:xyz789 CONTENT { created_at: d'2026-06-08T12:00:00Z', incremental: 1 }
```

### 1.10 RELATE — TIMEOUT

```sql
RELATE ONLY user:abc123->has_address->address:xyz789 TIMEOUT 30s
```

---

## 2. Edge cases

### 2.1 Nullable `#[Cast]`

Cast applies when hydrated value is an array/object row. Record-link strings skip cast. Nullable property accepts `null` without instantiating `Address`.

### 2.2 Nested graph traversal

- `out()` → `->`, `in()` → `<-`
- v1 WHERE in brackets: local fields only
- Statement-level `FETCH` uses output alias name

### 2.3 `SELECT VALUE`

| Aspect | `select(['name'])` | `selectValue('name')` |
|--------|-------------------|----------------------|
| SurrealQL | `SELECT name FROM …` | `SELECT VALUE name FROM …` |
| Result | `[{ name: "beau" }, …]` | `["beau", …]` |

Edge context: `FROM` table from edge metadata (`HasAddress` `in:` → `user`).

### 2.4 FETCH behavior

Without FETCH, record links stay as ids. FETCH follows output field name including graph aliases.

---

## 3. `#[Id]` DX recommendation

**v1: Option A** — single `#[Id]` marker; heterogeneous ids via SDK `RecordId`.

```php
#[Table('address')]
class Address extends Model
{
    #[Id] public RecordId $id;
}
```

---

## 4. SDK provides vs ORM must build

### SDK provides

Connection, `QueryExecutor`, `BoundQuery`, `RecordId`, `SelectQuery`, `RelateQuery`, `ValueMapper`, CRUD builders.

### ORM must build

Attributes, `Model`/`Edge`, AST + `SurrealQlCompiler`, `Operator`, `Hydrator`, `RelateBuilder`, `ConnectionManager`, literal `compile()` for tests.

---

## 5. Test strategy

1. **Compile tests (primary)** — one assertion per architecture compile-contract row; no DB.
2. **Metadata/hydration unit tests** — attribute resolution, nested `Cast`.
3. **Integration (optional)** — behind `SURREAL_TEST=1`.
4. **PHPStan level 8** on `src/` + fixtures.

---

## 6. v1 limitations

| Limitation | Notes |
|------------|-------|
| Correlated edge WHERE | `user.name` inside brackets → v2 |
| Graph bracket WHERE | Simple field refs only |
| `compile()` literals only | `toBoundQuery()` later |
| `#[Id]` marker only | No `IdKind` attribute |
| `Edge::out()` static DX | PHP: use `__callStatic` or `GraphSelectField::fromEdge()` |
| Connection required for `collect()` | `ConnectionManager` stub |
