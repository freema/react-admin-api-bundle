<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Interface;

use Freema\ReactAdminApiBundle\Request\UpdateDataRequest;
use Freema\ReactAdminApiBundle\Result\UpdateDataResult;

/**
 * Interface for repositories that support updating entities.
 */
interface DataRepositoryUpdateInterface
{
    /**
     * Update an existing entity with the provided data.
     */
    public function update(UpdateDataRequest $dataRequest): UpdateDataResult;
}
