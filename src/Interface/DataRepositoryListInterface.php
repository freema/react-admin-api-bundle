<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Interface;

use Freema\ReactAdminApiBundle\Request\ListDataRequest;
use Freema\ReactAdminApiBundle\Result\ListDataResult;

/**
 * Interface for repositories that support listing entities.
 */
interface DataRepositoryListInterface
{
    /**
     * List entities with pagination, sorting and filtering.
     */
    public function list(ListDataRequest $dataRequest): ListDataResult;
}
