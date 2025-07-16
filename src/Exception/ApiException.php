<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Exception;

use Symfony\Component\HttpFoundation\Response;

/**
 * Base exception class for API errors
 */
class ApiException extends \Exception
{
    public function __construct(
        string $message = "",
        int $code = Response::HTTP_INTERNAL_SERVER_ERROR,
        private readonly ?string $apiCode = null,
        private readonly ?array $context = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the API error code
     */
    public function getApiCode(): ?string
    {
        return $this->apiCode;
    }

    /**
     * Get additional context for the error
     */
    public function getContext(): ?array
    {
        return $this->context;
    }
}