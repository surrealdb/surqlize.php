# Surqlize

Surqlize is a SurrealDB ORM for PHP. It provides attribute-driven models, typed query helpers, graph edge traversal, mutation builders, schema tooling, and code generation on top of the `surrealdb/surrealdb.php` SDK.

The package is designed around a small core idea: describe your SurrealDB tables with PHP models and attributes, then compose SurrealQL through typed PHP APIs instead of string concatenation.

## Features

- Attribute-driven table and edge models with `#[Table]`, `#[Edge]`, `#[Id]`, `#[Schema]`, `#[Cast]`, `#[Search]`, `#[Vector]`, and `#[Geometry]`.
- AST-based query compilation for deterministic SurrealQL output.
- Bound execution through the SurrealDB PHP SDK, using `BoundQuery` for runtime values.
- Typed field adapters for IDE-friendly `select()`, `where()`, `orderBy()`, `fetch()`, and projection callbacks.
- Model helpers for create, update, upsert, delete, find, count, exists, refresh, and bulk mutations.
- Graph traversal fields for `->edge->table` and `<-edge<-table` SELECT projections.
- `RELATE` support for model-first relation creation.
- Schema management through raw `DEFINE` statements or the fluent schema DSL.
- CLI commands for field adapter generation, schema application, and memory footprint reports.
- Unit-test-first architecture with PHPStan level 8 coverage.

## Project Status

Surqlize currently targets early development workflows. The package requires PHP 8.4 and depends on `surrealdb/surrealdb.php` at `@dev`.

This repository's `composer.json` includes a local path repository for SDK development:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../surrealdb.php-refined"
        }
    ]
}
```

If you are consuming Surqlize outside this development workspace, make sure the SurrealDB PHP SDK is available through Packagist, a VCS repository, or your own path repository.

## Requirements

| Requirement | Version |
| --- | --- |
| PHP | `>=8.4` |
| SurrealDB PHP SDK | `surrealdb/surrealdb.php` `@dev` |
| PHPUnit, for development | `^11.0` |
| PHPStan, for development | `^2.0` |

## Installation

Install the package with Composer:

```bash
composer require surqlize/surqlize
```

If the SurrealDB PHP SDK is not yet available to your Composer setup, add an appropriate repository entry before installing:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/surrealdb/surrealdb.php"
        }
    ]
}
```

For local development in this repository:

```bash
composer install
composer test
composer analyse
```

## Quick Start

Define a model with table metadata:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Schemas\UserSchema;
use Surqlize\Attributes\Cast;
use Surqlize\Attributes\Id;
use Surqlize\Attributes\Schema;
use Surqlize\Attributes\Table;
use Surqlize\Model\Model;
use SurrealDB\SDK\Types\RecordId;

#[Table('user')]
#[Schema(UserSchema::class)]
final class User extends Model
{
    #[Id]
    public RecordId $id;

    public string $name;

    public int $age;

    #[Cast(Address::class)]
    public ?Address $address = null;
}
```

Configure the SDK executor during application bootstrap:

```php
use Surqlize\Connection\ConnectionManager;
use SurrealDB\SDK\Surreal;

$surreal = new Surreal(/* SDK connection configuration */);

ConnectionManager::set($surreal);
```

Compile a query:

```php
$sql = User::select(fn ($user) => [$user->name, $user->age])
    ->where(fn ($user) => [
        $user->name->eq('beau'),
        $user->age->gte(18),
    ])
    ->orderBy(fn ($user) => $user->name->asc())
    ->compile();

// SELECT name, age FROM user WHERE name = "beau" AND age >= 18 ORDER BY name ASC
```

Execute and hydrate models:

```php
$users = User::select(fn ($user) => [$user->id, $user->name, $user->age])
    ->where(fn ($user) => $user->age->gte(18))
    ->collectModels();

foreach ($users as $user) {
    echo $user->name;
}
```

You can also inject an executor for a single query:

```php
$users = User::query()
    ->withExecutor($surreal)
    ->collectModels();
```

## Model Attributes

| Attribute | Target | Purpose |
| --- | --- | --- |
| `#[Table('user')]` | Model class | Maps a model to a SurrealDB table. |
| `#[Edge('has_address', in: User::class, out: Address::class)]` | Edge class | Maps an edge model to a relation table and endpoint models. |
| `#[Id]` | Model property | Marks the model's `RecordId` property. |
| `#[Schema(UserSchema::class)]` | Model or edge class | Links a model to a schema contract. |
| `#[Cast(Address::class)]` | Model property | Hydrates nested values as another model. |
| `#[Search]` | Model property | Marks a field for search helper generation and typing. |
| `#[Vector(dimension: 3)]` | Model property | Marks a vector field and its expected dimension. |
| `#[Geometry]` | Model property | Marks a geometry-compatible field. |

`#[Id]` is a marker in v1. Record ID value types are handled by the SDK `RecordId` type.

## Models

All table models extend `Surqlize\Model\Model`.

```php
use Surqlize\Attributes\Table;
use Surqlize\Model\Model;

#[Table('address')]
final class Address extends Model
{
    public string $street;

    public int $number;

    public string $postcode;
}
```

The model base class provides query entry points:

| Method | Purpose |
| --- | --- |
| `select()` | Start a SELECT query with fields or a typed field callback. |
| `query()` | Start `SELECT *` for the model table. |
| `selectValue()` | Start a `SELECT VALUE` query. |
| `fields()` | Resolve the model's field set. |
| `relate()` | Start a model-first relation builder. |

It also provides common data operations:

| Method | Purpose |
| --- | --- |
| `create()` | Create a record and return a model. |
| `createQuery()` | Build a create mutation without immediately returning a model. |
| `upsert()` | Upsert a record by id. |
| `save()` | Create or update based on whether the model has a `RecordId`. |
| `delete()` | Delete the model's current record. |
| `all()` | Fetch all rows as models. |
| `find()` | Find one model by id. |
| `findOrFail()` | Find one model by id or throw `ModelNotFoundException`. |
| `count()` | Count records, optionally with a typed where callback. |
| `exists()` | Check whether at least one matching record exists. |
| `refresh()` | Reload the current model from the database. |
| `toArray()` | Serialize initialized model properties. |

## Schemas

Schemas implement `Surqlize\Model\SchemaContract`.

```php
<?php

declare(strict_types=1);

namespace App\Schemas;

use Surqlize\Model\SchemaContract;

final class UserSchema implements SchemaContract
{
    public function definitions(): array
    {
        return [
            'DEFINE TABLE user SCHEMAFULL;',
            'DEFINE FIELD name ON user TYPE string;',
            'DEFINE FIELD age ON user TYPE int;',
        ];
    }

    public function rules(): array
    {
        return [
            'name' => static fn (mixed $value): bool|string =>
                is_string($value) && $value !== '' ? true : 'Name is required.',
        ];
    }
}
```

`definitions()` returns SurrealDB schema statements. `rules()` returns PHP validation callbacks that run before persistence operations such as `create()` and `save()`.

### Schema DSL

The fluent schema DSL can generate `DEFINE` statements for tables, fields, analyzers, indexes, and assertions.

```php
use Surqlize\Schema\Schema;

$schema = Schema::table('article')->schemafull();

$schema->analyzer('english')
    ->tokenizers(['class'])
    ->filters(['lowercase']);

$schema
    ->field('title')
    ->string()
    ->assert(fn ($value) => $value->required()->minLength(3));

$schema
    ->field('email')
    ->string()
    ->assert(fn ($value) => $value->email())
    ->unique('idx_article_email');

$schema
    ->field('embedding')
    ->vector(3);

$schema
    ->index('idx_article_embedding')
    ->fields(['embedding'])
    ->hnsw(3);

$definitions = $schema->definitions();
```

You can mix raw strings and DSL objects in the same schema contract:

```php
use Surqlize\Model\SchemaContract;
use Surqlize\Schema\Schema;

final class ArticleSchema implements SchemaContract
{
    public function definitions(): array
    {
        return [
            'DEFINE TABLE legacy;',
            Schema::table('article')
                ->schemafull()
                ->field('title')
                ->string()
                ->assert(fn ($value) => $value->minLength(3)),
        ];
    }

    public function rules(): array
    {
        return [];
    }
}
```

Apply schemas from PHP:

```php
use Surqlize\Model\SchemaManager;

(new SchemaManager())->apply([
    User::class,
    Address::class,
], $surreal);
```

Or use the CLI:

```bash
vendor/bin/surqlize schema:apply surqlize.config.php
```

## Query Builder

The query builder composes a SurrealQL AST and can either compile a literal query string or execute a bound query through the SDK.

```php
$query = User::select(fn ($user) => [$user->name])
    ->where(fn ($user) => $user->name->eq('beau'));

$literal = $query->compile();
$bound = $query->toBoundQuery();
```

`compile()` is useful for debugging and deterministic tests. Runtime execution uses bound values through `toBoundQuery()`, `collect()`, `collectModels()`, `lazyModels()`, and `first()`.

### Typed Selects

```php
User::select(fn ($user) => [$user->name, $user->age])
    ->compile();

// SELECT name, age FROM user
```

### Typed Where Clauses

Return one predicate:

```php
User::select(fn ($user) => [$user->name])
    ->where(fn ($user) => $user->age->gte(18))
    ->compile();

// SELECT name FROM user WHERE age >= 18
```

Return multiple predicates to combine them with `AND`:

```php
User::select(fn ($user) => [$user->name])
    ->where(fn ($user) => [
        $user->name->eq('beau'),
        $user->age->gte(18),
    ])
    ->compile();

// SELECT name FROM user WHERE name = "beau" AND age >= 18
```

Field helpers include:

| Helper | Operator |
| --- | --- |
| `eq($value)` | `=` |
| `notEq($value)` | `!=` |
| `gt($value)` | `>` |
| `gte($value)` | `>=` |
| `lt($value)` | `<` |
| `lte($value)` | `<=` |
| `includes($value)` | `INCLUDES` |
| `contains($value)` | `CONTAINS` |
| `like($value)` | `LIKE` |
| `condition($operator, $value)` | Custom operator |

### Ordering

```php
User::select(fn ($user) => [$user->name])
    ->orderBy(fn ($user) => $user->name->asc())
    ->compile();

// SELECT name FROM user ORDER BY name ASC
```

You can also return the field and pass the direction separately:

```php
User::select(fn ($user) => [$user->name])
    ->orderBy(fn ($user) => $user->name, 'DESC')
    ->compile();

// SELECT name FROM user ORDER BY name DESC
```

### Fetching Links

```php
User::select(fn ($user) => [$user->name, $user->age])
    ->fetch(fn ($user) => $user->address)
    ->compile();

// SELECT name, age FROM user FETCH address
```

### Pagination

```php
User::query()
    ->page(page: 3, perPage: 25)
    ->compile();

// SELECT * FROM user LIMIT 25 START 50
```

Equivalent manual calls are available:

```php
User::query()
    ->limit(25)
    ->start(50);
```

### Projections And Aggregates

```php
use Surqlize\Query\Fields\Projection;

User::select(fn ($user) => [
        $user->age,
        Projection::count()->as('total'),
    ])
    ->groupBy(fn ($user) => $user->age)
    ->orderBy('total', 'DESC')
    ->compile();

// SELECT age, count() AS total FROM user GROUP BY age ORDER BY total DESC
```

Available projection helpers include `Projection::count()`, `Projection::sum($field)`, `Projection::mean($field)`, and `Projection::raw($expression)`.

### Advanced SELECT Clauses

Surqlize supports a broader set of SurrealQL SELECT clauses:

```php
User::select(['*'])
    ->omit('password')
    ->withIndex('idx_user_email')
    ->where(fn ($user) => $user->age->gte(18))
    ->split('tags')
    ->orderBy(fn ($user) => $user->name->desc())
    ->limit(10)
    ->start(20)
    ->fetch(fn ($user) => $user->address)
    ->timeout(5)
    ->tempFiles()
    ->explain(full: true)
    ->compile();
```

This compiles clauses in SurrealQL order:

```sql
SELECT * OMIT password FROM user WITH INDEX idx_user_email WHERE age >= 18 SPLIT tags ORDER BY name DESC LIMIT 10 START 20 FETCH address TIMEOUT 5s TEMPFILES EXPLAIN FULL
```

Other helpers include `withoutIndex()`, `groupAll()`, `withoutFrom()`, and `explainPlan()`.

### Selecting Values

```php
$name = User::selectValue(fn ($user) => $user->name)
    ->where(fn ($user) => $user->age->gte(18))
    ->first();
```

`SELECT VALUE` queries return scalar rows and cannot be hydrated with `collectModels()`.

### Query Execution

| Method | Result |
| --- | --- |
| `compile()` | Literal SurrealQL string. |
| `toBoundQuery()` | SDK `BoundQuery`. |
| `collect()` | List of raw rows. |
| `collectModels()` | List of hydrated models. |
| `lazyModels()` | Generator of hydrated models. |
| `first()` | First scalar or hydrated model, depending on query shape. |
| `explainPlan()` | Raw rows from an `EXPLAIN` query. |

## Mutations

Surqlize exposes both high-level model methods and mutation builder APIs.

### Create

```php
$user = User::create([
    'name' => 'beau',
    'age' => 27,
]);
```

Build the mutation explicitly:

```php
$query = User::createQuery(['name' => 'beau', 'age' => 27], id: 'beau');

$query->compile();
// CREATE user:beau CONTENT {"name":"beau","age":27} RETURN AFTER
```

### Update

```php
$user = User::findOrFail('beau');
$user->age = 28;
$user = $user->save();
```

Or update by predicate:

```php
User::updateWhere(fn ($user) => $user->age->gte(18))
    ->merge(['verified' => true])
    ->returnAfter()
    ->execute();
```

### Upsert

```php
$user = User::upsert([
    'name' => 'beau',
    'age' => 27,
], id: 'beau');
```

### Delete

```php
$user = User::findOrFail('beau');
$user->delete();
```

Or delete by predicate:

```php
User::deleteWhere(fn ($user) => $user->age->lt(13))
    ->returnBefore()
    ->execute();
```

### Mutation Payloads And Return Modes

Mutation builders support:

| Method | Purpose |
| --- | --- |
| `content($data)` | `CONTENT` payload. |
| `merge($data)` | `MERGE` payload. |
| `replace($data)` | `REPLACE` payload. |
| `patch($patches)` | `PATCH` payload. |
| `returnNone()` | `RETURN NONE`. |
| `returnBefore()` | `RETURN BEFORE`. |
| `returnAfter()` | `RETURN AFTER`. |
| `returnDiff()` | `RETURN DIFF`. |
| `returning($fields)` | Return selected fields. |
| `returningValue($field)` | Return one selected value. |
| `timeout($amount, $unit)` | Add a mutation timeout. |
| `execute()` | Execute and return the raw SDK result. |
| `firstModel()` | Execute and hydrate the first returned model. |

## Edges And Relations

SurrealDB relation tables are represented by edge models.

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Surqlize\Attributes\Edge;
use Surqlize\Attributes\Schema;
use Surqlize\Edge\Edge as EdgeModel;

#[Edge('has_address', in: User::class, out: Address::class)]
#[Schema(HasAddressSchema::class)]
final class HasAddress extends EdgeModel
{
}
```

Edge models inherit `RecordId $in` and `RecordId $out` endpoint properties from `Surqlize\Edge\Edge`.

### Graph SELECT Fields

Use graph fields inside a model SELECT to traverse relations.

```php
use App\Models\Address;
use App\Models\HasAddress;
use App\Models\User;
use Surqlize\Edge\Edge;

$query = User::select([
        'name',
        Edge::out(HasAddress::class)
            ->out(Address::class, fn ($address) => $address->postcode->includes('24'))
            ->as('address')
            ->fetch(),
    ])
    ->where(fn ($user) => $user->name->eq('beau'))
    ->fetch('address');

$query->compile();
```

The compiled query is:

```sql
SELECT name, ->has_address->address[WHERE postcode INCLUDES '24'] AS address WHERE name = "beau" FETCH address
```

If your static analysis setup has trouble with PHP's magic static call for `Edge::out()`, use the explicit factory:

```php
use Surqlize\Edge\GraphSelectField;
use Surqlize\Query\Ast\GraphDirection;

GraphSelectField::fromEdge(HasAddress::class, GraphDirection::Out)
    ->out(Address::class)
    ->as('address');
```

### Edge Endpoint Queries

An edge instance can query its endpoint tables:

```php
$edge = new HasAddress();

$users = $edge->in()
    ->select(fn ($user) => [$user->name])
    ->where(fn ($user) => $user->age->gt(27))
    ->collectModels();

$addresses = $edge->out()
    ->select(fn ($address) => [$address->postcode])
    ->collectModels();
```

### Creating Relations

Use `Model::relate($from)` to create a `RELATE` query.

```php
use Surqlize\Relate\Time;

User::relate($user)
    ->edge(HasAddress::class)
    ->with($address)
    ->content(['primary' => true])
    ->timeout(30, Time::Seconds)
    ->execute();
```

Both endpoint models must already have `RecordId` values. The builder validates that the source model matches the edge `in` endpoint and the target model matches the edge `out` endpoint.

## Search, Vector, And Geometry Helpers

Specialized field helpers can compile common SurrealDB search, vector, and geometry expressions.

```php
use Surqlize\Attributes\Geometry;
use Surqlize\Attributes\Search;
use Surqlize\Attributes\Table;
use Surqlize\Attributes\Vector;
use Surqlize\Model\Model;
use Surqlize\Query\Fields\GeometryField;
use Surqlize\Query\Fields\SearchField;
use Surqlize\Query\Fields\VectorField;

#[Table('searchable_article')]
final class SearchableArticle extends Model
{
    public string $title;

    #[Search]
    public string $body;

    /** @var list<float> */
    #[Vector(dimension: 3)]
    public array $embedding = [];

    /** @var list<float> */
    #[Geometry]
    public array $location = [];
}
```

Search:

```php
$body = new SearchField('body');

SearchableArticle::select(['title', $body->score()->as('score')])
    ->where(fn () => $body->matches('surreal orm'))
    ->orderBy('score', 'DESC')
    ->compile();

// SELECT title, search::score(1) AS score FROM searchable_article WHERE body @@ 'surreal orm' ORDER BY score DESC
```

Vector KNN:

```php
$embedding = new VectorField('embedding');

SearchableArticle::select(['title', $embedding->knnDistance()->as('distance')])
    ->where(fn () => $embedding->nearest([0.1, 0.2, 0.3], k: 10, effort: 40))
    ->orderBy('distance')
    ->compile();
```

Geometry distance:

```php
$location = new GeometryField('location');

SearchableArticle::select(['*', $location->distanceTo([4.9, 52.3])->as('distance')])
    ->where(fn () => $location->withinMeters([4.9, 52.3], 5000))
    ->orderBy('distance')
    ->compile();
```

## Field Adapters And Code Generation

Typed callbacks work through `FieldSet` classes. Surqlize can infer dynamic fields at runtime and can also generate explicit field adapters for better IDE and PHPStan support.

Example generated-style field class:

```php
<?php

declare(strict_types=1);

namespace App\Models\Fields;

use App\Models\User;
use Surqlize\Query\Fields\FieldSet;
use Surqlize\Query\Fields\NumericField;
use Surqlize\Query\Fields\RecordIdField;
use Surqlize\Query\Fields\RecordLinkField;
use Surqlize\Query\Fields\StringField;

final class UserFields extends FieldSet
{
    public readonly RecordIdField $id;
    public readonly StringField $name;
    public readonly NumericField $age;
    public readonly RecordLinkField $address;

    public function __construct()
    {
        parent::__construct(User::class);

        $this->id = new RecordIdField('id', table: 'user');
        $this->name = new StringField('name');
        $this->age = new NumericField('age');
        $this->address = new RecordLinkField('address');
    }
}
```

Create a `surqlize.config.php` file:

```php
<?php

declare(strict_types=1);

use App\Models\Address;
use App\Models\HasAddress;
use App\Models\User;

return [
    'models' => [
        User::class,
        Address::class,
        HasAddress::class,
    ],
    'fields_namespace' => 'App\\Models\\Fields',
    'fields_path' => __DIR__ . '/src/Models/Fields',
];
```

Generate field adapters:

```bash
vendor/bin/surqlize generate:fields surqlize.config.php
```

The generator writes `*Fields` classes and `*FieldTyping` traits.

## CLI

Surqlize ships a Composer binary named `surqlize`.

```bash
vendor/bin/surqlize generate:fields [config-path]
vendor/bin/surqlize schema:apply [config-path]
vendor/bin/surqlize memory:footprint [--iterations=1000] [--output=path]
```

In a source checkout without Composer's bin proxy, run:

```bash
php bin/surqlize generate:fields surqlize.config.php
php bin/surqlize schema:apply surqlize.config.php
php bin/surqlize memory:footprint
```

### CLI Config

`generate:fields` expects:

```php
return [
    'models' => [User::class, Address::class],
    'fields_namespace' => 'App\\Models\\Fields',
    'fields_path' => __DIR__ . '/src/Models/Fields',
];
```

`schema:apply` expects `models` plus an SDK query executor:

```php
return [
    'models' => [User::class, Address::class],
    'executor' => $surreal,
];
```

### Memory Footprint Reports

Generate a JSON memory report:

```bash
vendor/bin/surqlize memory:footprint
vendor/bin/surqlize memory:footprint --iterations=5000 --output=memory-report.json
```

Reports include retained memory deltas, peak memory deltas, real memory deltas, and scenario durations. The default scenarios cover metadata reflection, field-set resolution, typed query compilation, generated field adapter compilation, graph traversal compilation, edge endpoint queries, hydration, field generation, and record-id filters.

## Transactions

`ConnectionManager::transaction()` batches bound ORM queries into one transaction.

```php
use Surqlize\Connection\ConnectionManager;

ConnectionManager::transaction(function ($transaction): void {
    User::select(['name'])
        ->where(fn ($user) => $user->name->eq('beau'))
        ->withExecutor($transaction)
        ->collect();

    User::createQuery([
        'name' => 'tobie',
        'age' => 30,
    ], executor: $transaction)->execute();
});
```

If the callback throws, the transaction is rolled back and the exception is rethrown.

## Error Handling And Validation

Surqlize validates several contracts before building or executing queries:

- Model classes must extend `Surqlize\Model\Model`.
- Edge classes must extend `Surqlize\Edge\Edge`.
- Table and field identifiers are validated before compilation.
- Legacy string-based `where()` calls require an explicit operator.
- `findOrFail()` throws `Surqlize\Model\Exception\ModelNotFoundException`.
- Schema validation callbacks can return `true` or an error string.
- Persistence methods throw when required `RecordId` values are missing.
- `RELATE` validates edge endpoint classes and endpoint record ids.

## Security

Please report vulnerabilities privately. See `SECURITY.md` for the current vulnerability reporting and disclosure policy.

Surqlize also includes unit coverage for query-hardening behavior such as class-string validation and identifier safety.

## Development

Install dependencies:

```bash
composer install
```

Run the unit test suite:

```bash
composer test
```

Run static analysis:

```bash
composer analyse
```

Run the memory report:

```bash
composer memory
```

CI runs on PHP 8.4 and performs:

1. `composer validate --no-check-publish`
2. `composer audit --locked`
3. `composer run analyse`
4. `composer run test -- --log-junit build/logs/phpunit.xml`

## Repository Layout

```text
src/
  Attributes/    PHP attributes for models, schemas, edges, search, vector, and geometry fields.
  Benchmark/     Memory footprint scenarios and reporting.
  Connection/    Executor singleton and transaction support.
  Edge/          Edge model base class, edge metadata, endpoint queries, and graph SELECT fields.
  Generator/     Field adapter and typing trait generation.
  Model/         Model base class, metadata, validation, hydration, schema manager.
  Query/         AST nodes, compiler, typed field helpers, query and mutation builders.
  Relate/        RELATE builder and time units.
  Schema/        Fluent schema DSL.
  Support/       Internal validation helpers.

examples/
  Models/        Example User, Address, and HasAddress models.
  Schemas/       Example schema contracts.

tests/
  Fixtures/      Test models and generated-style field adapters.
  Unit/          Compile, model, schema, edge, relate, generator, security, and benchmark tests.

docs/
  architecture.md
  memory-footprint.md
  open-questions.md
  release.md
  research.md
```

## Known V1 Limitations

- The SDK dependency is currently `@dev`, and this checkout uses a local path repository during development.
- Most tests are compile-time or mock-executor tests. A bundled live database integration suite is not currently part of this repository.
- Passing raw string field names to `where()`, `orderBy()`, and related APIs is retained for migration, but typed callbacks are the preferred API.
- Graph traversal `WHERE` clauses support field predicates within the traversal segment; correlated outer-table references are deferred.
- `Model::relate($from)` is model-first only. Edge-first overloads are not supported.
- There is no field alias attribute in v1. PHP property names are expected to match SurrealDB field names.
- `ConnectionManager` is a singleton convenience. Use `withExecutor()` when you need per-query executor injection.

## Release Process

Releases are published from signed Git tags. The Packagist publish workflow validates GitHub tag verification before notifying Packagist.

See `docs/release.md` for the full release checklist.

## License

Surqlize is released under the MIT license. See `LICENSE`.
