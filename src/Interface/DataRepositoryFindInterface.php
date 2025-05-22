<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Interface;

use Freema\ReactAdminApiBundle\Dto\AdminApiDto;

/**
 * Interface for repositories that support finding a single entity.
 */
interface DataRepositoryFindInterface
{
    /**
     * Find an entity by ID and return it as a DTO.
     *
     * @param string|int $id
     */
    public function findWithDto($id): ?AdminApiDto;
}