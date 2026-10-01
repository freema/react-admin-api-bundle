<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Service;

use Freema\ReactAdminApiBundle\Exception\DtoClassNotFoundException;
use Freema\ReactAdminApiBundle\Exception\DtoCreationException;
use Freema\ReactAdminApiBundle\Exception\DtoInterfaceNotImplementedException;
use Freema\ReactAdminApiBundle\Interface\DtoInterface;

/**
 * Factory for creating DTO instances from array data.
 *
 * Request keys are assigned to the DTO's public, non-static properties only;
 * unknown keys and non-public properties are ignored.
 */
class DtoFactory
{
    /**
     * Create DTO instance from array data
     *
     * @param array<string, mixed>       $data
     * @param class-string<DtoInterface> $dtoClass
     *
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
            $reflection = new \ReflectionClass($dtoClass);
            $dto = $reflection->newInstance();

            foreach ($data as $property => $value) {
                $property = (string) $property;
                if (!$reflection->hasProperty($property)) {
                    continue;
                }

                // Only the public, non-static properties are the DTO's API
                // fields. Private, protected and static ones are internal
                // state a request must not be able to set.
                $reflectionProperty = $reflection->getProperty($property);
                if (!$reflectionProperty->isPublic() || $reflectionProperty->isStatic()) {
                    continue;
                }

                $reflectionProperty->setValue($dto, $value);
            }

            return $dto;
        } catch (\ReflectionException $e) {
            throw new DtoCreationException($dtoClass, 'Reflection error: '.$e->getMessage());
        } catch (\Throwable $e) {
            throw new DtoCreationException($dtoClass, 'Unexpected error: '.$e->getMessage());
        }
    }
}
