<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Request;

use Freema\ReactAdminApiBundle\Dto\AdminApiDto;
use Freema\ReactAdminApiBundle\Result\CreateDataResult;

/**
 * Represents a request to create a new entity.
 */
class CreateDataRequest
{
    /**
     * @param AdminApiDto $dataDto The data to create the entity from
     */
    public function __construct(private AdminApiDto $dataDto)
    {
    }

    public function getDataDto(): AdminApiDto
    {
        return $this->dataDto;
    }

    /**
     * Create a result from this request.
     *
     * @param AdminApiDto|null $dataDto The data that was created, or null if creation failed
     * @param bool $status Whether the operation was successful
     * @param array<string> $errorMessages Error messages if the operation failed
     */
    public function createResult(?AdminApiDto $dataDto = null, bool $status = true, array $errorMessages = []): CreateDataResult
    {
        return new CreateDataResult($dataDto, $status, $errorMessages);
    }
}