<?php

declare(strict_types=1);

namespace Abhimanev\Inflow\Resources;

use Abhimanev\Inflow\Client;

/**
 * inFlow stock adjustments — the right way to change stock quantities.
 *
 * Each adjustment records a delta (positive or negative) against a product
 * at a specific location, with a reason. inFlow accumulates these to derive
 * current on-hand quantity.
 *
 * Backing endpoint: /{companyId}/stock-adjustments
 */
class StockAdjustmentResource
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
        return $this->client->get('/stock-adjustments', $query);
    }

    /**
     * @return array<string, mixed>
     */
    public function find(string $id): array
    {
        return $this->client->get('/stock-adjustments/' . rawurlencode($id));
    }

    /**
     * Record a new stock adjustment.
     *
     * Required payload fields typically include:
     *   - productId (UUID)
     *   - locationId (UUID)
     *   - quantity (positive or negative delta)
     *   - adjustmentReasonId (UUID) — optional but recommended
     *   - notes (string) — optional
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function create(array $payload): array
    {
        return $this->client->post('/stock-adjustments', $payload);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function update(string $id, array $payload): array
    {
        return $this->client->put('/stock-adjustments/' . rawurlencode($id), $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function delete(string $id): array
    {
        return $this->client->delete('/stock-adjustments/' . rawurlencode($id));
    }
}
