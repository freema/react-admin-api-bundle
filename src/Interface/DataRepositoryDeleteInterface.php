<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Interface;

use Freema\ReactAdminApiBundle\Request\DeleteDataRequest;
use Freema\ReactAdminApiBundle\Request\DeleteManyDataRequest;
use Freema\ReactAdminApiBundle\Result\DeleteDataResult;

/**
 * Interface for repositories that support deleting entities.
 */
interface DataRepositoryDeleteInterface
{
    /**
     * Delete an entity by ID.
     */
    public function delete(DeleteDataRequest $dataRequest): DeleteDataResult;

    /**
     * Delete multiple entities by ID.
     */
    public function deleteMany(DeleteManyDataRequest $dataRequest): DeleteDataResult;
}
