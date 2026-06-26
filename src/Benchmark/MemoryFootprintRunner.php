<?php

declare(strict_types=1);

namespace Surqlize\Benchmark;

use Surqlize\Edge\GraphSelectField;
use Surqlize\Examples\Models\Address;
use Surqlize\Examples\Models\Fields\UserFields;
use Surqlize\Examples\Models\HasAddress;
use Surqlize\Examples\Models\User;
use Surqlize\Generator\FieldClassGenerator;
use Surqlize\Generator\FieldTypingTraitGenerator;
use Surqlize\Model\Hydrator;
use Surqlize\Model\ModelMetadata;
use Surqlize\Query\Ast\GraphDirection;
use Surqlize\Query\Fields\FieldSet;
use Surqlize\Query\Fields\FieldSetRegistry;
use Surqlize\Query\ModelQuery;
use SurrealDB\SDK\Types\RecordId;

final class MemoryFootprintRunner
{
    public function __construct(
        private readonly int $iterations = 1_000,
    ) {
        if ($this->iterations < 1) {
            throw new \InvalidArgumentException('Memory footprint iterations must be at least 1.');
        }
    }

    public function run(): MemoryFootprintReport
    {
        gc_collect_cycles();

        $baseline = $this->snapshot();
        $scenarios = [];

        foreach ($this->scenarios() as $scenario) {
            $scenarios[] = $this->measure($scenario);
        }

        return new MemoryFootprintReport(
            metadata: [
                'library' => 'surqlize/surqlize',
                'php_version' => PHP_VERSION,
                'php_sapi' => PHP_SAPI,
                'iterations' => $this->iterations,
                'generated_at' => gmdate(DATE_ATOM),
            ],
            baseline: $baseline,
            scenarios: $scenarios,
        );
    }

    /**
     * @return list<MemoryScenario>
     */
    private function scenarios(): array
    {
        return [
            new MemoryScenario(
                'metadata_resolution',
                'Resolve model and edge metadata through reflection/cache.',
                function (int $iterations): void {
                    for ($i = 0; $i < $iterations; $i++) {
                        ModelMetadata::for(User::class);
                        ModelMetadata::for(Address::class);
                        \Surqlize\Edge\EdgeMetadata::for(HasAddress::class);
                    }
                },
            ),
            new MemoryScenario(
                'typed_query_compile',
                'Compile typed select/where/order/fetch model queries.',
                function (int $iterations): void {
                    for ($i = 0; $i < $iterations; $i++) {
                        User::select(fn (FieldSet $user) => [$user->field('name'), $user->field('age')])
                            ->where(fn (FieldSet $user) => $user->field('age')->gte(18))
                            ->orderBy(fn (FieldSet $user) => $user->field('name')->asc())
                            ->fetch(fn (FieldSet $user) => $user->field('address'))
                            ->compile();
                    }
                },
            ),
			new MemoryScenario(
				'fieldset_resolution',
				'Resolve field set adapters and cache generated class guesses.',
				function (int $iterations): void {
					for ($i = 0; $i < $iterations; $i++) {
						FieldSetRegistry::resolve(User::class);
						FieldSetRegistry::resolve(Address::class);
						FieldSetRegistry::resolve(HasAddress::class);
					}
				},
			),
			new MemoryScenario(
				'generated_field_adapter_compile',
				'Compile typed model queries with generated field adapter property access.',
				function (int $iterations): void {
					for ($i = 0; $i < $iterations; $i++) {
						ModelQuery::forFieldSet(User::class, new UserFields(), fn (UserFields $user) => [$user->name, $user->age])
							->where(fn (UserFields $user) => $user->age->gte(18))
							->orderBy(fn (UserFields $user) => $user->name->asc())
							->fetch(fn (UserFields $user) => $user->address)
							->compile();
					}
				},
			),
            new MemoryScenario(
                'edge_graph_compile',
                'Compile graph traversal fields with typed bracket filters.',
                function (int $iterations): void {
                    for ($i = 0; $i < $iterations; $i++) {
                        User::select([
                            'name',
                            GraphSelectField::fromEdge(HasAddress::class, GraphDirection::Out)
                                ->out(Address::class, fn (FieldSet $address) => $address->field('postcode')->includes('24'))
                                ->as('address')
                                ->fetch(),
                        ])
                            ->where(fn (FieldSet $user) => $user->field('name')->eq('beau'))
                            ->compile();
                    }
                },
            ),
            new MemoryScenario(
                'edge_endpoint_compile',
                'Compile endpoint queries from an edge instance.',
                function (int $iterations): void {
                    $edge = new HasAddress();

                    for ($i = 0; $i < $iterations; $i++) {
                        $edge->in()
                            ->select(fn ($user) => [$user->name])
                            ->where(fn (FieldSet $user) => $user->field('age')->gt(27))
                            ->compile();
                    }
                },
            ),
            new MemoryScenario(
                'hydration',
                'Hydrate model rows with nested cast data and RecordId values.',
                function (int $iterations): void {
                    $hydrator = new Hydrator();
                    $row = [
                        'id' => RecordId::from('user', 'beau'),
                        'name' => 'beau',
                        'age' => 27,
                        'address' => [
                            'id' => RecordId::from('address', 'a1'),
                            'street' => 'Some Street',
                            'number' => 42,
                            'postcode' => '2940LD',
                        ],
                    ];

                    for ($i = 0; $i < $iterations; $i++) {
                        $hydrator->hydrate(User::class, $row);
                    }
                },
            ),
			new MemoryScenario(
				'hydration_bulk',
				'Hydrate batches of model rows using cached metadata lookups.',
				function (int $iterations): void {
					$hydrator = new Hydrator();
					$rows = [];

					for ($index = 0; $index < 10; $index++) {
						$rows[] = [
							'id' => RecordId::from('user', 'beau_' . $index),
							'name' => 'beau',
							'age' => 27,
							'unknown' => 'ignored',
							'address' => [
								'id' => RecordId::from('address', 'a' . $index),
								'street' => 'Some Street',
								'number' => 42,
								'postcode' => '2940LD',
								'unknown' => 'ignored',
							],
						];
					}

					for ($i = 0; $i < $iterations; $i++) {
						foreach ($rows as $row) {
							$hydrator->hydrate(User::class, $row);
						}
					}
				},
			),
            new MemoryScenario(
                'field_generation',
                'Generate field adapter and typing trait source strings.',
                function (int $iterations): void {
                    $fieldGenerator = new FieldClassGenerator();
                    $traitGenerator = new FieldTypingTraitGenerator();
                    $checksum = 0;

                    for ($i = 0; $i < $iterations; $i++) {
                        $checksum += strlen($fieldGenerator->generate(User::class, 'Generated\\Fields'));
                        $checksum += strlen($fieldGenerator->generate(Address::class, 'Generated\\Fields'));
                        $checksum += strlen($traitGenerator->generate(User::class, 'Generated\\Fields'));
                    }

                    if ($checksum === 0) {
                        throw new \RuntimeException('Field generation scenario produced no output.');
                    }
                },
            ),
            new MemoryScenario(
                'record_id_filters',
                'Compile RecordId filters for string, integer, and array id payloads.',
                function (int $iterations): void {
                    for ($i = 0; $i < $iterations; $i++) {
                        User::select(fn (FieldSet $user) => [$user->field('name')])
                            ->where(fn (FieldSet $user) => $user->field('id')->eq('beau'))
                            ->compile();
                        User::select(fn (FieldSet $user) => [$user->field('name')])
                            ->where(fn (FieldSet $user) => $user->field('id')->eq(123))
                            ->compile();
                        User::select(fn (FieldSet $user) => [$user->field('name')])
                            ->where(fn (FieldSet $user) => $user->field('id')->eq(new RecordId('user', ['log', 123])))
                            ->compile();
                    }
                },
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function measure(MemoryScenario $scenario): array
    {
        gc_collect_cycles();

        if (function_exists('memory_reset_peak_usage')) {
            memory_reset_peak_usage();
        }

        $before = $this->snapshot();
        $startedAt = hrtime(true);

        $scenario->run($this->iterations);

        $durationNanoseconds = hrtime(true) - $startedAt;
        gc_collect_cycles();
        $after = $this->snapshot();

        return [
            'name' => $scenario->name,
            'description' => $scenario->description,
            'iterations' => $this->iterations,
            'duration_ms' => round($durationNanoseconds / 1_000_000, 3),
            'usage_before_bytes' => $before['usage_bytes'],
            'usage_after_bytes' => $after['usage_bytes'],
            'usage_delta_bytes' => $after['usage_bytes'] - $before['usage_bytes'],
            'real_usage_before_bytes' => $before['real_usage_bytes'],
            'real_usage_after_bytes' => $after['real_usage_bytes'],
            'real_usage_delta_bytes' => $after['real_usage_bytes'] - $before['real_usage_bytes'],
            'peak_usage_bytes' => $after['peak_usage_bytes'],
            'peak_delta_bytes' => $after['peak_usage_bytes'] - $before['usage_bytes'],
            'peak_real_usage_bytes' => $after['peak_real_usage_bytes'],
            'peak_real_delta_bytes' => $after['peak_real_usage_bytes'] - $before['real_usage_bytes'],
        ];
    }

    /**
     * @return array{usage_bytes: int, real_usage_bytes: int, peak_usage_bytes: int, peak_real_usage_bytes: int}
     */
    private function snapshot(): array
    {
        return [
            'usage_bytes' => memory_get_usage(false),
            'real_usage_bytes' => memory_get_usage(true),
            'peak_usage_bytes' => memory_get_peak_usage(false),
            'peak_real_usage_bytes' => memory_get_peak_usage(true),
        ];
    }
}
