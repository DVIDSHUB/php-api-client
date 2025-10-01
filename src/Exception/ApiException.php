<?php

declare(strict_types=1);

namespace DvidsApi\Exception;

use Exception;

/**
 * Base exception for all DVIDS API related errors
 */
class ApiException extends Exception
{
    public function __construct(
        string $message = '', 
        private readonly int $statusCode = 0, 
        private readonly ?array $errorData = null
    ) {
        parent::__construct($message, $statusCode);
    }

    /**
     * Get HTTP status code
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Get raw error data from API response
     */
    public function getErrorData(): ?array
    {
        return $this->errorData;
    }
}