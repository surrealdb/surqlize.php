# Open questions

Unresolved design decisions for surqlize.php. Agents must not guess silently —
add an entry here and pick the v1 default documented in **Resolution (v1)**.

---

## 1. `#[Id]` attribute variants

**Problem:** Design doc shows multiple `#[Id(Type::String)]`, `#[Id(Type::Number)]`, etc.
on the same property — invalid PHP (duplicate property declarations).

**Options:**
- A) Single `#[Id]`; `RecordId` already carries `string|int|array|object` id kinds (SDK).
- B) `#[Id(kind: IdKind::Uuid)]` enum parameter on one property.
- C) Separate typed id value objects instead of unified `RecordId`.

**Resolution (v1):** **A** — `#[Id]` marker only; type safety via `RecordId` + PHPStan generics
on `Model` optional in v2. Document that `RecordId::$id` holds the heterogeneous id part.

---

## 2. `zipcode` vs `postcode`

**Problem:** `Address` defines `zipcode`; graph query example filters `postcode`.

**Options:**
- A) Rename to `postcode` everywhere.
- B) Keep `zipcode` on model; design doc example is wrong.
- C) Support `#[Column('postcode')]` field alias attribute.

**Resolution (v1):** **A** — use `postcode` in fixtures and tests to match SurrealQL examples.
Update design doc in a later pass.

---

## 3. `#[Schema]` typo in design doc

**Problem:** `User` class shows `$[Schema(UserSchema::class)]` instead of `#[Schema(...)]`.

**Resolution (v1):** Treat as `#[Schema]` attribute; fixtures use correct syntax.

---

## 4. `selectValue` FROM clause

**Problem:** Design comment says `SELECT VALUE name FROM WHERE age > 27` (missing table).

**Resolution (v1):** Compiler emits `SELECT VALUE name FROM user WHERE age > 27` using
edge metadata `in` class (`User` → table `user`).

---

## 5. Query parameter binding vs literal compile

**Problem:** SDK uses `BoundQuery` with bound parameters; compile tests expect literal SurrealQL.

**Options:**
- A) `compile()` returns literal string for tests; `toBoundQuery()` for execution.
- B) `compile()` returns parameterized form with placeholders.

**Resolution (v1):** **A** — `compile(): string` for deterministic tests; execution path may
wrap SDK later.

---

## 6. `User::relate()` overloads

**Problem:** Design doc showed both `User::relate($user)` and `User::relate(HasAddress::class)`.

**Resolution (v1):** **Model-first only** — `relate(Model $from): RelateBuilder` then
`->edge(HasAddress::class)->with($address)`. Edge-first `relate(string $edgeClass)` is not supported.

---

## 7. Edge WHERE scoping (`user.name` inside edge bracket)

**Problem:** `SELECT ->has_address[WHERE user.name="beau"]->address[...]` requires
correlating outer table in sub-where.

**Resolution (v1):** Defer to v2. v1 supports simple field WHERE in graph brackets only
(`postcode INCLUDES '24'`). Document limitation in research.md.

---

## 8. Connection / executor injection

**Problem:** `Model::select()` needs `QueryExecutor` for `collect()` but not for `compile()`.

**Options:**
- A) Global `ConnectionManager::get()`.
- B) Required `Surreal $db` argument on `collect()`.
- C) Optional executor on builder constructor via `Model::using($surreal)`.

**Resolution (v1):** **A** for ergonomics + **C** as override. `ConnectionManager` stub OK.

---

## 9. SDK package availability

**Problem:** `surrealdb/surrealdb.php` may not be on Packagist during development.

**Resolution (v1):** `composer.json` uses path repository to `../surrealdb.php-refined`.

---

## 11. `Edge::out()` static vs instance `in()` / `out()`

**Problem:** Design doc uses `Edge::out(HasAddress::class)` (static) and `$edge->in()` (instance).
PHP cannot declare static and instance methods with the same name when static calls must resolve
unambiguously. `Edge` also has `$in` / `$out` record properties.

**Resolution (v1):** Instance traversal via `__call('in'|'out')` → `EdgeQuery`. Static graph
factories via `__callStatic('in'|'out')` → `GraphSelectField`, or explicitly
`GraphSelectField::fromEdge($class, GraphDirection::Out)`.

---

## 10. `HasAddress::hasUserUnderAge` design doc bug

**Problem:** Method named `hasUserUnderAge` but WHERE uses `age > 27` (over, not under).

**Resolution (v1):** Tests follow commented SurrealQL, not method name semantics. Flag for
design doc fix.
