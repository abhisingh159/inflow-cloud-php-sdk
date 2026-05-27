<?php

declare(strict_types=1);

namespace Abhimanev\Inflow;

use Abhimanev\Inflow\Exception\ApiException;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class Client
{
    private Config $config;
    private ClientInterface $http;

    public function __construct(Config $config, ?ClientInterface $http = null)
    {
        $this->config = $config;
        $this->http = $http ?? new GuzzleClient([
            'base_uri' => $config->getBaseUrl() . '/',
            'timeout' => $config->getTimeout(),
            'http_errors' => false,
        ]);
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public function get(string $path, array $query = []): array
    {
        return $this->request('GET', $path, [
            'query' => $query,
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function post(string $path, array $payload = []): array
    {
        return $this->request('POST', $path, [
            'json' => $payload,
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function put(string $path, array $payload = []): array
    {
        return $this->request('PUT', $path, [
            'json' => $payload,
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function patch(string $path, array $payload = []): array
    {
        return $this->request('PATCH', $path, [
            'json' => $payload,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function delete(string $path): array
    {
        return $this->request('DELETE', $path);
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, array $options = []): array
    {
        $options['headers'] = array_merge(
            $options['headers'] ?? [],
            $this->defaultHeaders()
        );

        if (isset($options['query']) && $options['query'] === []) {
            unset($options['query']);
        }

        try {
            $response = $this->http->request($method, $this->normalizePath($path), $options);
        } catch (RequestException $e) {
            $response = $e->getResponse();
            if ($response === null) {
                throw new ApiException(
                    sprintf('HTTP request failed: %s', $e->getMessage()),
                    0,
                    '',
                    null,
                    $e
                );
            }
            return $this->handleResponse($response);
        } catch (GuzzleException | Throwable $e) {
            throw new ApiException(
                sprintf('HTTP request failed: %s', $e->getMessage()),
                0,
                '',
                null,
                $e
            );
        }

        return $this->handleResponse($response);
    }

    /**
     * @return array<string, mixed>
     */
    private function handleResponse(ResponseInterface $response): array
    {
        $status = $response->getStatusCode();
        $body = (string) $response->getBody();

        $data = null;
        if ($body !== '') {
            $decoded = json_decode($body, true);
            if (is_array($decoded)) {
                $data = $decoded;
            } elseif (json_last_error() !== JSON_ERROR_NONE && $status >= 200 && $status < 300) {
                throw new ApiException(
                    sprintf('Failed to decode JSON response: %s', json_last_error_msg()),
                    $status,
                    $body
                );
            }
        }

        if ($status < 200 || $status >= 300) {
            $message = sprintf('inFlow API error (HTTP %d)', $status);
            if (is_array($data)) {
                if (isset($data['message']) && is_string($data['message'])) {
                    $message .= ': ' . $data['message'];
                } elseif (isset($data['error']) && is_string($data['error'])) {
                    $message .= ': ' . $data['error'];
                }
            }
            throw new ApiException($message, $status, $body, is_array($data) ? $data : null);
        }

        if ($body === '' || $data === null) {
            return [];
        }

        return $data;
    }

    /**
     * @return array<string, string>
     */
    private function defaultHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->config->getApiKey(),
            'company_id' => $this->config->getCompanyId(),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    private function normalizePath(string $path): string
    {
        return ltrim($path, '/');
    }
}
