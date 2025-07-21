<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Request;

use Freema\ReactAdminApiBundle\Result\DeleteDataResult;

/**
 * Represents a request to delete an entity.
 */
class DeleteDataRequest
{
    /**
     * @param string|int $id The ID of the entity to delete
     */
    public function __construct(private string|int $id)
    {
    }

    /**
     * @return string|int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Create a result from this request.
     *
     * @param bool          $status        Whether the operation was successful
     * @param array<string> $errorMessages Error messages if the operation failed
     */
    public function createResult(bool $status = true, array $errorMessages = []): DeleteDataResult
    {
        return new DeleteDataResult($status, $errorMessages);
    }
}
