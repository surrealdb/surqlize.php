<?php

declare(strict_types=1);

namespace Surqlize\Tests\Unit\Query;

use Surqlize\Attributes\Geometry;
use Surqlize\Attributes\Search;
use Surqlize\Attributes\Table;
use Surqlize\Attributes\Vector;
use Surqlize\Model\Model;
use Surqlize\Query\Fields\GeometryField;
use Surqlize\Query\Fields\SearchField;
use Surqlize\Query\Fields\VectorField;
use Surqlize\Tests\TestCase;

final class SearchVectorGeoCompileTest extends TestCase
{
    public function test_search_score_and_match_compile(): void
    {
        $body = new SearchField('body');

        $this->assertSame(
            'SELECT title, search::score(1) AS score FROM searchable_article WHERE body @@ \'surreal orm\' ORDER BY score DESC',
            SearchableArticle::select(['title', $body->score()->as('score')])
                ->where(fn () => $body->matches('surreal orm'))
                ->orderBy('score', 'DESC')
                ->compile(),
        );
    }

    public function test_vector_knn_compile(): void
    {
        $embedding = new VectorField('embedding');

        $this->assertSame(
            'SELECT title, vector::distance::knn() AS distance FROM searchable_article WHERE embedding <|10, 40|> [ 0.1, 0.2, 0.3 ] ORDER BY distance ASC',
            SearchableArticle::select(['title', $embedding->knnDistance()->as('distance')])
                ->where(fn () => $embedding->nearest([0.1, 0.2, 0.3], k: 10, effort: 40))
                ->orderBy('distance')
                ->compile(),
        );
    }

    public function test_geo_distance_predicate_compile(): void
    {
        $location = new GeometryField('location');

        $this->assertSame(
            'SELECT *, geo::distance(location, [ 4.9, 52.3 ]) AS distance FROM searchable_article WHERE geo::distance(location, [ 4.9, 52.3 ]) <= 5000 ORDER BY distance ASC',
            SearchableArticle::select(['*', $location->distanceTo([4.9, 52.3])->as('distance')])
                ->where(fn () => $location->withinMeters([4.9, 52.3], 5000))
                ->orderBy('distance')
                ->compile(),
        );
    }
}

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
