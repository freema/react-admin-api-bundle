<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle;

use Doctrine\ORM\EntityManagerInterface;
use Freema\ReactAdminApiBundle\Dto\AdminApiDto;
use Freema\ReactAdminApiBundle\Interface\AdminEntityInterface;
use Freema\ReactAdminApiBundle\Request\CreateDataRequest;
use Freema\ReactAdminApiBundle\Result\CreateDataResult;

/**
 * Trait to help implement the DataRepositoryCreateInterface.
 */
trait CreateTrait
{
    /**
     * Create an entity from data.
     */
    public function create(CreateDataRequest $dataRequest): CreateDataResult
    {
        $dto = $dataRequest->getDataDto();

        try {
            $entityManager = $this->getEntityManager();
            if (!$entityManager instanceof EntityManagerInterface) {
                throw new \LogicException('Entity manager not available');
            }

            $entities = $this->createEntitiesFromDto($dto);

            foreach ($entities as $entity) {
                $entityManager->persist($entity);
            }

            $entityManager->flush();

            return $dataRequest->createResult(static::mapToDto($entities[0]));
        } catch (\Exception $e) {
            return $dataRequest->createResult(null, false, [$e->getMessage()]);
        }
    }

    /**
     * Get the entity manager.
     */
    abstract public function getEntityManager();

    /**
     * Map an entity to a DTO.
     */
    abstract public static function mapToDto(AdminEntityInterface $entity): AdminApiDto;

    /**
     * Create entities from a DTO.
     *
     * @return array<AdminEntityInterface>
     */
    abstract public function createEntitiesFromDto(AdminApiDto $dto): array;
}
