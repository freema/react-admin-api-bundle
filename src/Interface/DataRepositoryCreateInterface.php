<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Interface;

use Freema\ReactAdminApiBundle\Request\CreateDataRequest;
use Freema\ReactAdminApiBundle\Result\CreateDataResult;

/**
 * Interface for repositories that support creating entities.
 */
interface DataRepositoryCreateInterface
{
    /**
     * Create a new entity from the provided data.
     */
    public function create(CreateDataRequest $dataRequest): CreateDataResult;
}