<?php

declare(strict_types=1);

namespace Abhimanev\Inflow\Exception;

use Throwable;

class ApiException extends InflowException
{
    private int $statusCode;
    private string $responseBody;
    /** @var array<string, mixed>|null */
    private ?array $responseData;

    /**
     * @param array<string, mixed>|null $responseData
     */
    public function __construct(
        string $message,
        int $statusCode = 0,
        string $responseBody = '',
        ?array $responseData = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode, $previous);
        $this->statusCode = $statusCode;
        $this->responseBody = $responseBody;
        $this->responseData = $responseData;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getResponseBody(): string
    {
        return $this->responseBody;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getResponseData(): ?array
    {
        return $this->responseData;
    }
}
