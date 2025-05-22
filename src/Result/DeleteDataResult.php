<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Result;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Result of a delete operation.
 */
class DeleteDataResult
{
    /**
     * @param bool $status Whether the operation was successful
     * @param array<string> $errorMessages Error messages if the operation failed
     */
    public function __construct(
        private bool $status,
        private array $errorMessages = []
    ) {
    }

    public function isSuccess(): bool
    {
        return $this->status;
    }

    /**
     * @return array<string>
     */
    public function getErrorMessages(): array
    {
        return $this->errorMessages;
    }

    /**
     * Create a JSON response with the result data formatted for React Admin.
     */
    public function createResponse(): JsonResponse
    {
        if (!$this->status) {
            return new JsonResponse([
                'error' => implode(', ', $this->errorMessages),
            ], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(['success' => true]);
    }
}