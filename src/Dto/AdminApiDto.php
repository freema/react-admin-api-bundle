<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Dto;

use Freema\ReactAdminApiBundle\Interface\AdminEntityInterface;
use Freema\ReactAdminApiBundle\Interface\DtoInterface;

/**
 * Base DTO class for all API data transfer objects.
 */
abstract class AdminApiDto implements DtoInterface
{
    /**
     * Get the entity class that this DTO maps to.
     *
     * @return class-string<AdminEntityInterface>
     */
    abstract public static function getMappedEntityClass(): string;

    /**
     * Create a DTO instance from an entity.
     */
    abstract public static function createFromEntity(AdminEntityInterface $entity): self;

    /**
     * Convert the DTO to an array representation suitable for API responses.
     *
     * @return array<string, mixed>
     */
    abstract public function toArray(): array;
}
