<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Event\Common;

use Freema\ReactAdminApiBundle\Event\ReactAdminApiEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Event dispatched when an exception occurs in the API
 * Allows custom exception handling and response modification
 */
class ApiExceptionEvent extends ReactAdminApiEvent
{
    private ?JsonResponse $response = null;

    public function __construct(
        string $resource,
        Request $request,
        private readonly \Throwable $exception,
        private readonly string $operation,
    ) {
        parent::__construct($resource, $request);
    }

    /**
     * Get the exception that occurred
     */
    public function getException(): \Throwable
    {
        return $this->exception;
    }

    /**
     * Get the operation that was being performed
     */
    public function getOperation(): string
    {
        return $this->operation;
    }

    /**
     * Get the response that will be sent
     */
    public function getResponse(): ?JsonResponse
    {
        return $this->response;
    }

    /**
     * Set a custom response
     */
    public function setResponse(JsonResponse $response): self
    {
        $this->response = $response;

        return $this;
    }

    /**
     * Check if a custom response has been set
     */
    public function hasResponse(): bool
    {
        return $this->response !== null;
    }

    /**
     * Get exception information
     */
    public function getExceptionInfo(): array
    {
        return [
            'type' => get_class($this->exception),
            'message' => $this->exception->getMessage(),
            'code' => $this->exception->getCode(),
            'file' => $this->exception->getFile(),
            'line' => $this->exception->getLine(),
            'trace' => $this->exception->getTraceAsString(),
            'previous' => $this->exception->getPrevious() ? get_class($this->exception->getPrevious()) : null,
        ];
    }

    /**
     * Check if this is a validation exception
     */
    public function isValidationException(): bool
    {
        return $this->exception instanceof \Freema\ReactAdminApiBundle\Exception\ValidationException;
    }

    /**
     * Check if this is a not found exception
     */
    public function isNotFoundException(): bool
    {
        return $this->exception instanceof \Freema\ReactAdminApiBundle\Exception\EntityNotFoundException;
    }

    /**
     * Check if this is a client error (4xx)
     */
    public function isClientError(): bool
    {
        return $this->isValidationException() || $this->isNotFoundException();
    }

    /**
     * Check if this is a server error (5xx)
     */
    public function isServerError(): bool
    {
        return !$this->isClientError();
    }

    /**
     * Get suggested HTTP status code
     */
    public function getSuggestedStatusCode(): int
    {
        if ($this->isValidationException()) {
            return 400;
        }

        if ($this->isNotFoundException()) {
            return 404;
        }

        return 500;
    }

    /**
     * Create a standardized error response
     */
    public function createErrorResponse(string $error, string $message, ?int $statusCode = null): JsonResponse
    {
        $statusCode = $statusCode ?? $this->getSuggestedStatusCode();

        $data = [
            'error' => $error,
            'message' => $message,
            'timestamp' => (new \DateTimeImmutable())->format('c'),
            'resource' => $this->getResource(),
            'operation' => $this->operation,
        ];

        return new JsonResponse($data, $statusCode);
    }
}
