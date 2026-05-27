# inFlow Cloud PHP SDK

Standalone PHP SDK for the [inFlow Cloud API](https://cloudapi.inflowinventory.com/docs/index.html).

Framework-agnostic — drop it into any PHP 8.1+ project: vanilla PHP scripts, Laravel, Symfony, Slim, CLI tools, cron workers, AWS Lambda, or any CMS/e-commerce platform (WordPress, Magento, Drupal, …). The SDK has zero knowledge of any framework; it only knows HTTP and the inFlow API.

## Requirements

- PHP 8.1+
- [Guzzle](https://github.com/guzzle/guzzle) 7.8+

## Installation

```bash
composer require abhimanev/inflow-cloud-php-sdk
```

## Configuration

The SDK reads credentials from a `Config` object. Do **not** hardcode credentials in your application — load them from environment variables or your framework's config store.

You'll need:
- an active inFlow account with the API access add-on (or a trial)
- administrator rights
- an **API key** and your **companyId** — generate both at [app.inflowinventory.com/options/integrations](https://app.inflowinventory.com/options/integrations)

The SDK handles the URL structure (`/{companyId}/...`), auth header (`Authorization: Bearer {key}`), and the versioned `Accept` header for you. You only ever pass the resource path (e.g. `/products`).

```php
use Abhimanev\Inflow\Config;
use Abhimanev\Inflow\Inflow;

$config = new Config(
    apiKey:    getenv('INFLOW_API_KEY'),
    companyId: getenv('INFLOW_COMPANY_ID'),
    baseUrl:   'https://cloudapi.inflowinventory.com' // optional
);

$inflow = new Inflow($config);
```

## Usage

### Get products

```php
$products = $inflow->products()->list([
    'limit' => 50,
]);

foreach ($products as $product) {
    echo $product['name'] ?? '', PHP_EOL;
}
```

### Find a single product

```php
$product = $inflow->products()->find('product-id-here');
```

### Create a sales order

```php
$order = $inflow->salesOrders()->create([
    'customerId' => 'customer-id-here',
    'orderNumber' => 'SO-1042',
    'lines' => [
        [
            'productId' => 'product-id-here',
            'quantity'  => 2,
            'unitPrice' => 19.99,
        ],
    ],
]);

echo 'Created sales order: ', $order['id'] ?? '(no id)', PHP_EOL;
```

> Field names above are illustrative. Confirm exact field names against the [inFlow Cloud API reference](https://cloudapi.inflowinventory.com/docs/index.html).

### Inventory

```php
$levels = $inflow->inventory()->stockLevels([
    'productId' => 'product-id-here',
]);
```

### Customers and vendors

```php
$customer = $inflow->customers()->create([
    'name'  => 'Acme Inc.',
    'email' => 'orders@acme.test',
]);

$vendors = $inflow->vendors()->list();
```

## Error handling

The SDK throws `Abhimanev\Inflow\Exception\ApiException` for any non-2xx HTTP response, and `Abhimanev\Inflow\Exception\InflowException` for configuration or transport issues. `ApiException` extends `InflowException`, so catching the latter handles both.

```php
use Abhimanev\Inflow\Exception\ApiException;
use Abhimanev\Inflow\Exception\InflowException;

try {
    $inflow->salesOrders()->create($payload);
} catch (ApiException $e) {
    error_log(sprintf(
        'inFlow API failed [%d]: %s — body: %s',
        $e->getStatusCode(),
        $e->getMessage(),
        $e->getResponseBody()
    ));
} catch (InflowException $e) {
    error_log('inFlow transport error: ' . $e->getMessage());
}
```

## Advanced: custom HTTP client

`Client` accepts any PSR-18 / Guzzle `ClientInterface`. This is useful for testing (mock responses) or for plugging in a shared HTTP client with retries / middleware / logging:

```php
use Abhimanev\Inflow\Client;
use Abhimanev\Inflow\Config;
use Abhimanev\Inflow\Inflow;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\HandlerStack;

$stack = HandlerStack::create();
// $stack->push(your_retry_middleware());

$http = new GuzzleClient([
    'base_uri'    => 'https://cloudapi.inflowinventory.com/',
    'timeout'     => 30,
    'http_errors' => false,
    'handler'     => $stack,
]);

$config = new Config($apiKey, $companyId);
$inflow = new Inflow($config, new Client($config, $http));
```

## Available resources

| Method | Resource | Endpoints |
|---|---|---|
| `products()`     | `ProductResource`    | `/products`, `/products/{id}` |
| `customers()`    | `CustomerResource`   | `/customers`, `/customers/{id}` |
| `salesOrders()`  | `SalesOrderResource` | `/sales-orders`, `/sales-orders/{id}` |
| `inventory()`    | `InventoryResource`  | `/products/summary`, `/products/{id}/summary` (read-only — see note below) |
| `vendors()`      | `VendorResource`     | `/vendors`, `/vendors/{id}` |

Each resource exposes `list()`, `find()`, `create()`, `update()`, `delete()` — **except `inventory()`**, which is read-only. inFlow has no direct "set stock" endpoint; stock changes go through stock-adjustments / stock-counts / stock-transfers (planned for a future SDK release). Calling `inventory()->create/update/delete()` throws `BadMethodCallException` with an actionable message.

## License

MIT.
