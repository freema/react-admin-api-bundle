<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Request;

use Freema\ReactAdminApiBundle\Dto\AdminApiDto;
use Freema\ReactAdminApiBundle\Result\UpdateDataResult;

/**
 * Represents a request to update an existing entity.
 */
class UpdateDataRequest
{
    /**
     * @param string|int $id The ID of the entity to update
     * @param AdminApiDto $dataDto The data to update the entity with
     */
    public function __construct(private string|int $id, private AdminApiDto $dataDto)
    {
    }

    /**
     * @return string|int
     */
    public function getId()
    {
        return $this->id;
    }

    public function getDataDto(): AdminApiDto
    {
        return $this->dataDto;
    }

    /**
     * Create a result from this request.
     *
     * @param AdminApiDto|null $dataDto The updated data, or null if update failed
     * @param bool $status Whether the operation was successful
     * @param array<string> $errorMessages Error messages if the operation failed
     */
    public function createResult(?AdminApiDto $dataDto = null, bool $status = true, array $errorMessages = []): UpdateDataResult
    {
        return new UpdateDataResult($dataDto, $status, $errorMessages);
    }
}