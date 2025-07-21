<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Interface;

use Freema\ReactAdminApiBundle\Request\ListDataRequest;
use Freema\ReactAdminApiBundle\Result\ListDataResult;

/**
 * Interface for repositories that support listing entities related to another entity.
 */
interface RelatedDataRepositoryListInterface
{
    /**
     * List entities related to the provided entity with pagination, sorting and filtering.
     */
    public function listRelatedTo(ListDataRequest $dataRequest, RelatedEntityInterface $entity): ListDataResult;
}
