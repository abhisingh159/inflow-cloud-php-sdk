<?php

declare(strict_types=1);

namespace Abhimanev\Inflow\Resources;

use Abhimanev\Inflow\Client;
use BadMethodCallException;

/**
 * Read-only inventory summaries from inFlow.
 *
 * Backed by `/{companyId}/products/summary` (multi) and
 * `/{companyId}/products/{productId}/summary` (single). inFlow does not
 * expose a direct "set stock" write endpoint — stock changes go through
 * stock-adjustments, stock-counts, or stock-transfers, which will be wired
 * into dedicated resource classes in a future release.
 *
 * The write methods on this class therefore throw BadMethodCallException
 * with an actionable message rather than silently 404'ing.
 */
class InventoryResource
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Get inventory summaries for multiple products.
     *
     * @param array<string, mixed> $query  Pass `filter[productId]`, `filter[locationId]`, etc. per inFlow docs.
     * @return array<string, mixed>
     */
    public function list(array $query = []): array
    {
        return $this->client->get('/products/summary', $query);
    }

    /**
     * Get inventory summary for a single product.
     *
     * @return array<string, mixed>
     */
    public function find(string $productId): array
    {
        return $this->client->get('/products/' . rawurlencode($productId) . '/summary');
    }

    /**
     * Alias for list() — kept for backwards compatibility with earlier SDK versions.
     *
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public function stockLevels(array $query = []): array
    {
        return $this->list($query);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     * @throws BadMethodCallException Always — inFlow has no direct "create inventory" endpoint.
     */
    public function create(array $payload): array
    {
        unset($payload);
        throw new BadMethodCallException(
            'InventoryResource::create() is not supported by the inFlow API. '
            . 'Use stock-adjustments / stock-counts / stock-transfers instead (dedicated resources coming in a future SDK release).'
        );
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     * @throws BadMethodCallException Always — inFlow has no direct "update inventory" endpoint.
     */
    public function update(string $id, array $payload): array
    {
        unset($id, $payload);
        throw new BadMethodCallException(
            'InventoryResource::update() is not supported by the inFlow API. '
            . 'To change stock quantity, create a stock-adjustment for the delta '
            . '(dedicated resource coming in a future SDK release).'
        );
    }

    /**
     * @return array<string, mixed>
     * @throws BadMethodCallException Always — inFlow has no direct "delete inventory" endpoint.
     */
    public function delete(string $id): array
    {
        unset($id);
        throw new BadMethodCallException(
            'InventoryResource::delete() is not supported by the inFlow API.'
        );
    }
}
