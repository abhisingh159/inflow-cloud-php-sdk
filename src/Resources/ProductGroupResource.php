<?php

declare(strict_types=1);

namespace Abhimanev\Inflow\Resources;

use Abhimanev\Inflow\Client;

/**
 * inFlow product groups (parent + variant matrix).
 *
 * @see https://cloudapi.inflowinventory.com/docs/api/swagger.json — ProductGroup
 */
class ProductGroupResource
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public function list(array $query = []): array
    {
        return $this->client->get('/product-groups', $query);
    }

    /**
     * @return array<string, mixed>
     */
    public function find(string $id, array $query = []): array
    {
        return $this->client->get('/product-groups/' . rawurlencode($id), $query);
    }

    /**
     * Upsert via PUT /product-groups (id in the body).
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function create(array $payload): array
    {
        if (empty($payload['productGroupId'])) {
            $payload['productGroupId'] = Client::uuid4();
        }

        return $this->client->put('/product-groups', $payload);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function update(string $id, array $payload): array
    {
        $payload['productGroupId'] = $id;

        return $this->client->put('/product-groups', $payload);
    }
}
