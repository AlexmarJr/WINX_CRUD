<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class ProductSearchRepository
{
    private const INDEX_DEFINITION = [
        'settings' => [
            'number_of_shards' => 1,
            'number_of_replicas' => 0,
            'analysis' => [
                'analyzer' => [
                    'folded' => [
                        'type' => 'custom',
                        'tokenizer' => 'standard',
                        'filter' => ['lowercase', 'asciifolding'],
                    ],
                ],
            ],
        ],
        'mappings' => [
            'dynamic' => 'strict',
            'properties' => [
                'id' => ['type' => 'keyword'],
                'tenancy_id' => ['type' => 'keyword'],
                'category_id' => ['type' => 'keyword'],
                'name' => [
                    'type' => 'text',
                    'analyzer' => 'folded',
                    'fields' => [
                        'keyword' => ['type' => 'keyword', 'ignore_above' => 256],
                        'suggest' => ['type' => 'search_as_you_type', 'analyzer' => 'folded'],
                    ],
                ],
                'description' => ['type' => 'text', 'analyzer' => 'folded'],
                'category_name' => [
                    'type' => 'text',
                    'analyzer' => 'folded',
                    'fields' => ['keyword' => ['type' => 'keyword', 'ignore_above' => 256]],
                ],
                'price' => ['type' => 'scaled_float', 'scaling_factor' => 100],
                'stock' => ['type' => 'integer'],
                'status' => ['type' => 'keyword'],
                'created_at' => ['type' => 'date'],
            ],
        ],
    ];

    public function index(Product $product): void
    {
        $this->ensureIndex();
        $response = $this->request('PUT', '/'.$this->indexName().'/_doc/'.$product->id, $this->document($product));
        $this->assertSuccessful($response);
    }

    public function remove(string $productId): void
    {
        $response = $this->request('DELETE', '/'.$this->indexName().'/_doc/'.$productId);

        if ($response->status() !== 404) {
            $this->assertSuccessful($response);
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{ids: array<int, string>, total: int}
     */
    public function search(string $tenancyId, string $query, array $filters, int $page, int $perPage): array
    {
        $offset = ($page - 1) * $perPage;

        if ($offset + $perPage > 10000) {
            throw ValidationException::withMessages(['page' => ['A busca permite navegar até os primeiros 10.000 resultados.']]);
        }

        $this->ensureIndex();
        $conditions = [['term' => ['tenancy_id' => $tenancyId]]];

        if (isset($filters['category_id'])) {
            $conditions[] = ['term' => ['category_id' => $filters['category_id']]];
        }
        if (isset($filters['status'])) {
            $conditions[] = ['term' => ['status' => $filters['status']]];
        }
        if (($filters['availability'] ?? null) === 'in_stock') {
            $conditions[] = ['range' => ['stock' => ['gt' => 0]]];
        } elseif (($filters['availability'] ?? null) === 'out_of_stock') {
            $conditions[] = ['term' => ['stock' => 0]];
        }
        if (isset($filters['min_price']) || isset($filters['max_price'])) {
            $range = [];
            if (isset($filters['min_price'])) {
                $range['gte'] = (float) $filters['min_price'];
            }
            if (isset($filters['max_price'])) {
                $range['lte'] = (float) $filters['max_price'];
            }
            $conditions[] = ['range' => ['price' => $range]];
        }

        $sortFields = [
            'name' => 'name.keyword',
            'category' => 'category_name.keyword',
            'price' => 'price',
            'stock' => 'stock',
            'status' => 'status',
        ];
        $sortBy = $filters['sort_by'] ?? null;
        $sort = $sortBy === null
            ? [['_score' => 'desc'], ['id' => 'asc']]
            : [[$sortFields[$sortBy] => ['order' => $filters['sort_dir'] ?? 'asc', 'missing' => '_last']], ['id' => 'asc']];

        $response = $this->request('POST', '/'.$this->indexName().'/_search', [
            'from' => $offset,
            'size' => $perPage,
            'track_total_hits' => true,
            '_source' => false,
            'query' => [
                'bool' => [
                    'filter' => $conditions,
                    'should' => [
                        ['multi_match' => [
                            'query' => $query,
                            'fields' => ['name^5', 'category_name^2', 'description'],
                            'type' => 'best_fields',
                            'fuzziness' => 'AUTO',
                        ]],
                        ['multi_match' => [
                            'query' => $query,
                            'type' => 'bool_prefix',
                            'fields' => ['name.suggest^3', 'name.suggest._2gram^3', 'name.suggest._3gram^3'],
                        ]],
                    ],
                    'minimum_should_match' => 1,
                ],
            ],
            'sort' => $sort,
        ]);
        $this->assertSuccessful($response);

        return [
            'ids' => array_map(fn (array $hit): string => $hit['_id'], $response->json('hits.hits', [])),
            'total' => (int) $response->json('hits.total.value', 0),
        ];
    }

    /** @return array<int, string> */
    public function suggest(string $tenancyId, string $query): array
    {
        $this->ensureIndex();
        $response = $this->request('POST', '/'.$this->indexName().'/_search', [
            'size' => 10,
            '_source' => ['name'],
            'query' => [
                'bool' => [
                    'filter' => [['term' => ['tenancy_id' => $tenancyId]]],
                    'must' => [['multi_match' => [
                        'query' => $query,
                        'type' => 'bool_prefix',
                        'fields' => ['name.suggest', 'name.suggest._2gram', 'name.suggest._3gram'],
                    ]]],
                ],
            ],
        ]);
        $this->assertSuccessful($response);

        return array_slice(array_values(array_unique(array_filter(array_map(
            fn (array $hit): ?string => $hit['_source']['name'] ?? null,
            $response->json('hits.hits', []),
        )))), 0, 5);
    }

    public function rebuild(): int
    {
        $response = $this->request('DELETE', '/'.$this->indexName());
        if ($response->status() !== 404) {
            $this->assertSuccessful($response);
        }
        $this->createIndex();

        $count = 0;
        Product::query()->with('category')->chunkById(500, function ($products) use (&$count): void {
            $lines = [];
            foreach ($products as $product) {
                $lines[] = json_encode(['index' => ['_id' => $product->id]], JSON_THROW_ON_ERROR);
                $lines[] = json_encode($this->document($product), JSON_THROW_ON_ERROR);
                $count++;
            }
            $response = $this->requestBulk(implode("\n", $lines)."\n");
            $this->assertSuccessful($response);
            if ($response->json('errors') === true) {
                throw new ServiceUnavailableHttpException(null, 'Falha ao indexar alguns produtos.');
            }
        });

        $this->assertSuccessful($this->request('POST', '/'.$this->indexName().'/_refresh'));

        return $count;
    }

    /** @return array<string, mixed> */
    private function document(Product $product): array
    {
        return [
            'id' => $product->id,
            'tenancy_id' => $product->tenancy_id,
            'category_id' => $product->category_id,
            'name' => $product->name,
            'description' => $product->description,
            'category_name' => $product->category?->name,
            'price' => (float) $product->price,
            'stock' => $product->stock,
            'status' => $product->status->value,
            'created_at' => $product->created_at?->toIso8601String(),
        ];
    }

    private function ensureIndex(): void
    {
        $response = $this->request('HEAD', '/'.$this->indexName());
        if ($response->status() === 404) {
            $this->createIndex();
        } else {
            $this->assertSuccessful($response);
        }
    }

    private function createIndex(): void
    {
        $response = $this->request('PUT', '/'.$this->indexName(), self::INDEX_DEFINITION);
        if ($response->status() === 400 && $response->json('error.type') === 'resource_already_exists_exception') {
            return;
        }
        $this->assertSuccessful($response);
    }

    /** @param array<string, mixed>|null $body */
    private function request(string $method, string $path, ?array $body = null): Response
    {
        try {
            return $this->client()->send($method, $this->url().$path, $body === null ? [] : ['json' => $body]);
        } catch (ConnectionException) {
            throw new ServiceUnavailableHttpException(null, 'Busca temporariamente indisponível.');
        }
    }

    private function requestBulk(string $body): Response
    {
        try {
            return $this->client()->withBody($body, 'application/x-ndjson')->post($this->url().'/'.$this->indexName().'/_bulk');
        } catch (ConnectionException) {
            throw new ServiceUnavailableHttpException(null, 'Busca temporariamente indisponível.');
        }
    }

    private function client(): PendingRequest
    {
        $client = Http::acceptJson()->timeout(10);
        $apiKey = config('search.elasticsearch.api_key');

        return $apiKey ? $client->withToken($apiKey, 'ApiKey') : $client;
    }

    private function assertSuccessful(Response $response): void
    {
        if ($response->failed()) {
            Log::warning('Falha na comunicação com Elasticsearch.', ['status' => $response->status()]);
            throw new ServiceUnavailableHttpException(null, 'Busca temporariamente indisponível.');
        }
    }

    private function url(): string
    {
        return rtrim(config('search.elasticsearch.url'), '/');
    }

    private function indexName(): string
    {
        return config('search.elasticsearch.index');
    }
}
