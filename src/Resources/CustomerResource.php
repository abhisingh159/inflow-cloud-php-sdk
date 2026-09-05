<?php

declare(strict_types=1);

namespace Abhimanev\Inflow\Resources;

use Abhimanev\Inflow\Client;

class CustomerResource
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
        return $this->client->get('/customers', $query);
    }

    /**
     * @return array<string, mixed>
     */
    public function find(string $id): array
    {
        return $this->client->get('/customers/' . rawurlencode($id));
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function create(array $payload): array
    {
        if (empty($payload['customerId'])) {
            $payload['customerId'] = Client::uuid4();
        }

        return $this->client->put('/customers', $payload);
    }

    /**
     * inFlow upserts via PUT /customers with the id in the body.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function update(string $id, array $payload): array
    {
        $payload['customerId'] = $id;

        return $this->client->put('/customers', $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function delete(string $id): array
    {
        return $this->client->delete('/customers/' . rawurlencode($id));
    }
}
