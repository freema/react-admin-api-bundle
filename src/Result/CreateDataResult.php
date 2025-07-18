<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Result;

use Freema\ReactAdminApiBundle\Dto\AdminApiDto;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Result of a create operation.
 */
class CreateDataResult
{
    /**
     * @param AdminApiDto|null $data          The created data, or null if creation failed
     * @param bool             $status        Whether the operation was successful
     * @param array<string>    $errorMessages Error messages if the operation failed
     */
    public function __construct(
        private ?AdminApiDto $data,
        private bool $status,
        private array $errorMessages = [],
    ) {
    }

    public function getData(): ?AdminApiDto
    {
        return $this->data;
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

        if ($this->data === null) {
            return new JsonResponse([
                'error' => 'Entity could not be created',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse($this->data->toArray(), Response::HTTP_CREATED);
    }
}
