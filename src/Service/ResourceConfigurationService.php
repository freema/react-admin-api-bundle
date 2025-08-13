<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Service;

use Freema\ReactAdminApiBundle\Interface\DtoInterface;

class ResourceConfigurationService
{
    /**
     * @param array<string, array<string, mixed>> $resources
     */
    public function __construct(
        private readonly array $resources,
    ) {
    }

    /**
     * Get the DTO class for the given resource path.
     *
     * @return class-string<DtoInterface>
     */
    public function getResourceDtoClass(string $resource): string
    {
        $resourceConfig = $this->getResourceConfig($resource);

        $resourceDtoClass = $resourceConfig['dto_class'];
        if (!is_string($resourceDtoClass) || !is_subclass_of($resourceDtoClass, DtoInterface::class)) {
            throw new \LogicException(sprintf('Resource DTO class "%s" must be a valid class string implementing DtoInterface', is_string($resourceDtoClass) ? $resourceDtoClass : gettype($resourceDtoClass)));
        }

        return $resourceDtoClass;
    }

    /**
     * Get the entity class for the given resource path.
     *
     * @return class-string
     */
    public function getResourceEntityClass(string $resource): string
    {
        $dtoClass = $this->getResourceDtoClass($resource);

        return $dtoClass::getMappedEntityClass();
    }

    /**
     * Get the resource configuration for the given resource path.
     *
     * @return array<string, mixed>
     *
     * @throws \InvalidArgumentException if the resource is not configured
     */
    public function getResourceConfig(string $resource): array
    {
        if (!isset($this->resources[$resource])) {
            throw new \InvalidArgumentException(sprintf('Resource path not configured: %s', $resource));
        }

        return $this->resources[$resource];
    }

    /**
     * Get all configured resource paths.
     *
     * @return array<string>
     */
    public function getConfiguredResources(): array
    {
        return array_keys($this->resources);
    }

    /**
     * Check if resource is configured.
     */
    public function hasResource(string $resource): bool
    {
        return isset($this->resources[$resource]);
    }
}
