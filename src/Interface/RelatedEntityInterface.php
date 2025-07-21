<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Interface;

/**
 * Interface for entities that can be related to other entities.
 */
interface RelatedEntityInterface
{
    /**
     * Get the alias of the entity for use in related entity queries.
     */
    public function getAlias(): string;
}
