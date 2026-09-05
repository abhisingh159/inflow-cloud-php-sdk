<?php

declare(strict_types=1);

namespace Abhimanev\Inflow\Resources;

use Abhimanev\Inflow\Client;

class SalesOrderResource
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
        return $this->client->get('/sales-orders', $query);
    }

    /**
     * @return array<string, mixed>
     */
    public function find(string $id): array
    {
        return $this->client->get('/sales-orders/' . rawurlencode($id));
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function create(array $payload): array
    {
        if (empty($payload['salesOrderId'])) {
            $payload['salesOrderId'] = Client::uuid4();
        }

        return $this->client->put('/sales-orders', $payload);
    }

    /**
     * inFlow upserts via PUT /sales-orders with the id in the body.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function update(string $id, array $payload): array
    {
        $payload['salesOrderId'] = $id;

        return $this->client->put('/sales-orders', $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function delete(string $id): array
    {
        return $this->client->delete('/sales-orders/' . rawurlencode($id));
    }
}
