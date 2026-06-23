<?php

declare(strict_types=1);

namespace Surqlize\Tests\Unit\Schema;

use SurrealDB\SDK\Contracts\QueryExecutor;
use SurrealDB\SDK\Query\BoundQuery;
use Surqlize\Attributes\Schema as SchemaAttribute;
use Surqlize\Attributes\Table;
use Surqlize\Model\Model;
use Surqlize\Model\SchemaContract;
use Surqlize\Model\SchemaManager;
use Surqlize\Schema\Schema;
use Surqlize\Tests\TestCase;

final class SchemaDslTest extends TestCase
{
    public function test_schema_dsl_compiles_model_owned_definitions(): void
    {
        $schema = Schema::table('article')->schemafull();
        $schema->analyzer('english')->tokenizers(['class'])->filters(['lowercase']);

        $schema
            ->field('title')->string()->assert(fn ($value) => $value->required()->minLength(3))
            ->field('email')->string()->assert(fn ($value) => $value->email())->unique('idx_article_email')
            ->field('embedding')->vector(3)
            ->index('idx_article_embedding')->fields(['embedding'])->hnsw(3);

        $definitions = $schema->definitions();

        $this->assertSame([
            'DEFINE ANALYZER english TOKENIZERS class FILTERS lowercase;',
            'DEFINE TABLE article SCHEMAFULL;',
            'DEFINE FIELD title ON TABLE article TYPE string ASSERT $value != NONE AND string::len($value) >= 3;',
            'DEFINE FIELD email ON TABLE article TYPE string ASSERT string::is::email($value);',
            'DEFINE FIELD embedding ON TABLE article TYPE array<float>;',
            'DEFINE INDEX idx_article_email ON TABLE article FIELDS email UNIQUE;',
            'DEFINE INDEX idx_article_embedding ON TABLE article FIELDS embedding HNSW DIMENSION 3 DIST COSINE;',
        ], $definitions);
    }

    public function test_schema_manager_applies_strings_and_schema_definition_objects(): void
    {
        $executor = new SchemaCapturingExecutor();

        (new SchemaManager())->apply([SchemaDslArticle::class], $executor);

        $this->assertSame([
            'DEFINE TABLE legacy;',
            'DEFINE TABLE article SCHEMAFULL;',
            'DEFINE FIELD title ON TABLE article TYPE string ASSERT string::len($value) >= 3;',
        ], array_map(static fn (BoundQuery $query): string => $query->query, $executor->queries));
    }
}

final class SchemaCapturingExecutor implements QueryExecutor
{
    /** @var list<BoundQuery> */
    public array $queries = [];

    public function query(BoundQuery $query): mixed
    {
        $this->queries[] = $query;

        return [];
    }
}

#[Table('schema_dsl_article')]
#[SchemaAttribute(SchemaDslArticleSchema::class)]
final class SchemaDslArticle extends Model
{
    public string $title;
}

final class SchemaDslArticleSchema implements SchemaContract
{
    public function definitions(): array
    {
        return [
            'DEFINE TABLE legacy;',
            Schema::table('article')
                ->schemafull()
                ->field('title')->string()->assert(fn ($value) => $value->minLength(3)),
        ];
    }

    public function rules(): array
    {
        return [];
    }
}
