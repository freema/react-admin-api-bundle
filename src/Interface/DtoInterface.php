<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Interface;

/**
 * Interface for DTOs that are used in the API.
 */
interface DtoInterface
{
    /**
     * Get the entity class that this DTO maps to.
     *
     * @return class-string<AdminEntityInterface>
     */
    public static function getMappedEntityClass(): string;

    /**
     * Create a DTO instance from an entity.
     */
    public static function createFromEntity(AdminEntityInterface $entity): self;

    /**
     * Convert the DTO to an array representation suitable for API responses.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
