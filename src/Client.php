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
    /**
     * inFlow API version pinned by this SDK release. The API requires this
     * in the Accept header to avoid surprise breaking changes.
     *
     * @see https://cloudapi.inflowinventory.com/docs/index.html
     */
    public const API_VERSION = '2026-02-24';

    /** Max attempts for a throttled (429/503) request before giving up. */
    private const MAX_ATTEMPTS = 5;

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
     * Generate a v4 UUID. inFlow upserts require the entity id in the body and
     * expect a client-generated GUID on insert.
     */
    public static function uuid4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
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

        $url = $this->normalizePath($path);

        // inFlow rate-limits; over a large sync (1k+ records) requests will get
        // 429s. Transparently back off and retry (honouring Retry-After) so a
        // single throttle doesn't fail the whole sync job.
        for ($attempt = 1; ; $attempt++) {
            try {
                $response = $this->http->request($method, $url, $options);
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
            } catch (GuzzleException | Throwable $e) {
                throw new ApiException(
                    sprintf('HTTP request failed: %s', $e->getMessage()),
                    0,
                    '',
                    null,
                    $e
                );
            }

            $status = $response->getStatusCode();
            if (in_array($status, [429, 503], true) && $attempt < self::MAX_ATTEMPTS) {
                $this->backoff($response, $attempt);
                continue;
            }

            return $this->handleResponse($response);
        }
    }

    /** Sleep before retrying a throttled request: Retry-After if given, else exponential. */
    private function backoff(ResponseInterface $response, int $attempt): void
    {
        $retryAfter = $response->getHeaderLine('Retry-After');
        $seconds = is_numeric($retryAfter) && $retryAfter !== ''
            ? (float) $retryAfter
            : 0.5 * (2 ** ($attempt - 1));
        $seconds = min($seconds, 10.0); // hard cap so a job never stalls on one call

        usleep((int) ($seconds * 1_000_000));
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
            $detail  = $this->extractErrorDetail($data);
            if ($detail !== '') {
                $message .= ': ' . $detail;
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
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json;version=' . self::API_VERSION,
        ];
    }

    /**
     * Every inFlow Cloud endpoint is rooted at /{companyId}/...
     * We auto-prepend it so resource classes can keep using their bare path
     * (e.g. "/products").
     */
    private function normalizePath(string $path): string
    {
        return rawurlencode($this->config->getCompanyId()) . '/' . ltrim($path, '/');
    }

    /**
     * Pull a human-readable message out of a JSON error body. inFlow uses
     * ASP.NET Core / RFC 7807 problem+json in many places, so we look for
     * the common fields.
     *
     * @param array<string, mixed>|null $data
     */
    private function extractErrorDetail(?array $data): string
    {
        if (!is_array($data)) {
            return '';
        }

        foreach (['detail', 'message', 'error', 'errorMessage', 'title'] as $key) {
            if (isset($data[$key]) && is_string($data[$key]) && trim($data[$key]) !== '') {
                return $data[$key];
            }
        }

        // Validation errors often arrive as {"errors": {"field": ["msg1", ...]}}
        if (isset($data['errors']) && is_array($data['errors'])) {
            $parts = [];
            foreach ($data['errors'] as $field => $msgs) {
                $msgList = is_array($msgs) ? implode(', ', array_map('strval', $msgs)) : (string) $msgs;
                $parts[] = is_string($field) ? sprintf('%s: %s', $field, $msgList) : $msgList;
            }
            if ($parts !== []) {
                return implode('; ', $parts);
            }
        }

        return '';
    }
}
