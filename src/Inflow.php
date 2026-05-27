<?php

declare(strict_types=1);

namespace Abhimanev\Inflow;

use Abhimanev\Inflow\Resources\CustomerResource;
use Abhimanev\Inflow\Resources\InventoryResource;
use Abhimanev\Inflow\Resources\ProductResource;
use Abhimanev\Inflow\Resources\SalesOrderResource;
use Abhimanev\Inflow\Resources\VendorResource;

class Inflow
{
    private Config $config;
    private Client $client;

    private ?ProductResource $products = null;
    private ?CustomerResource $customers = null;
    private ?SalesOrderResource $salesOrders = null;
    private ?InventoryResource $inventory = null;
    private ?VendorResource $vendors = null;

    public function __construct(Config $config, ?Client $client = null)
    {
        $this->config = $config;
        $this->client = $client ?? new Client($config);
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function products(): ProductResource
    {
        return $this->products ??= new ProductResource($this->client);
    }

    public function customers(): CustomerResource
    {
        return $this->customers ??= new CustomerResource($this->client);
    }

    public function salesOrders(): SalesOrderResource
    {
        return $this->salesOrders ??= new SalesOrderResource($this->client);
    }

    public function inventory(): InventoryResource
    {
        return $this->inventory ??= new InventoryResource($this->client);
    }

    public function vendors(): VendorResource
    {
        return $this->vendors ??= new VendorResource($this->client);
    }
}
