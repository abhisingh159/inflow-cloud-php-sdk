<?php

declare(strict_types=1);

namespace Abhimanev\Inflow\Resources;

use Abhimanev\Inflow\Client;

class InventoryResource
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
        return $this->client->get('/inventory', $query);
    }

    /**
     * @return array<string, mixed>
     */
    public function find(string $id): array
    {
        return $this->client->get('/inventory/' . rawurlencode($id));
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function create(array $payload): array
    {
        return $this->client->post('/inventory', $payload);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function update(string $id, array $payload): array
    {
        return $this->client->put('/inventory/' . rawurlencode($id), $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function delete(string $id): array
    {
        return $this->client->delete('/inventory/' . rawurlencode($id));
    }

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public function stockLevels(array $query = []): array
    {
        return $this->client->get('/inventory/stock-levels', $query);
    }
}
