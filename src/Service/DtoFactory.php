<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Service;

use Freema\ReactAdminApiBundle\Interface\DtoInterface;
use Freema\ReactAdminApiBundle\Exception\DtoClassNotFoundException;
use Freema\ReactAdminApiBundle\Exception\DtoInterfaceNotImplementedException;
use Freema\ReactAdminApiBundle\Exception\DtoCreationException;
use ReflectionClass;
use ReflectionProperty;

/**
 * Factory for creating DTO instances from array data
 */
class DtoFactory
{
    /**
     * Create DTO instance from array data
     * 
     * @param array<string, mixed> $data
     * @param class-string<DtoInterface> $dtoClass
     * @return DtoInterface
     */
    public function createFromArray(array $data, string $dtoClass): DtoInterface
    {
        if (!class_exists($dtoClass)) {
            throw new DtoClassNotFoundException($dtoClass);
        }

        if (!is_subclass_of($dtoClass, DtoInterface::class)) {
            throw new DtoInterfaceNotImplementedException($dtoClass);
        }

        try {
            $reflection = new ReflectionClass($dtoClass);
            $dto = $reflection->newInstance();

            foreach ($data as $property => $value) {
                if ($reflection->hasProperty($property)) {
                    $reflectionProperty = $reflection->getProperty($property);
                    
                    // Make property accessible
                    $reflectionProperty->setAccessible(true);
                    
                    // Set the value
                    $reflectionProperty->setValue($dto, $value);
                }
            }

            return $dto;
        } catch (\ReflectionException $e) {
            throw new DtoCreationException($dtoClass, "Reflection error: " . $e->getMessage());
        } catch (\Throwable $e) {
            throw new DtoCreationException($dtoClass, "Unexpected error: " . $e->getMessage());
        }
    }
}