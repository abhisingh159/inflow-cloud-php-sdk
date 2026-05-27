<?php

declare(strict_types=1);

namespace Abhimanev\Inflow;

use Abhimanev\Inflow\Exception\InflowException;

final class Config
{
    public const DEFAULT_BASE_URL = 'https://cloudapi.inflowinventory.com';

    private string $apiKey;
    private string $companyId;
    private string $baseUrl;
    private float $timeout;

    public function __construct(
        string $apiKey,
        string $companyId,
        string $baseUrl = self::DEFAULT_BASE_URL,
        float $timeout = 30.0
    ) {
        $apiKey = trim($apiKey);
        $companyId = trim($companyId);

        if ($apiKey === '') {
            throw new InflowException('API key must not be empty.');
        }

        if ($companyId === '') {
            throw new InflowException('Company ID must not be empty.');
        }

        $baseUrl = trim($baseUrl);
        if ($baseUrl === '') {
            $baseUrl = self::DEFAULT_BASE_URL;
        }

        $this->apiKey = $apiKey;
        $this->companyId = $companyId;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function getCompanyId(): string
    {
        return $this->companyId;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getTimeout(): float
    {
        return $this->timeout;
    }
}
