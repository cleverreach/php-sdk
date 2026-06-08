<?php

declare(strict_types=1);

namespace CleverReach\SDK\Exception;

class CleverReachException extends \RuntimeException
{
    public function __construct(
        string $message,
        private readonly ?int $statusCode = null,
        private readonly ?string $responseBody = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode ?? 0, $previous);
    }

    public function statusCode(): ?int {
        return $this->statusCode;
    }

    public function responseBody(): ?string {
        return $this->responseBody;
    }
}
