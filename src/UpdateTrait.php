<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle;

use Doctrine\ORM\EntityManagerInterface;
use Freema\ReactAdminApiBundle\Dto\AdminApiDto;
use Freema\ReactAdminApiBundle\Exception\EntityNotFoundException;
use Freema\ReactAdminApiBundle\Interface\AdminEntityInterface;
use Freema\ReactAdminApiBundle\Request\UpdateDataRequest;
use Freema\ReactAdminApiBundle\Result\UpdateDataResult;

/**
 * Trait to help implement the DataRepositoryUpdateInterface.
 */
trait UpdateTrait
{
    /**
     * Update an entity from data.
     *
     * @throws EntityNotFoundException
     */
    public function update(UpdateDataRequest $dataRequest): UpdateDataResult
    {
        $id = $dataRequest->getId();
        $dto = $dataRequest->getDataDto();

        try {
            $entityManager = $this->getEntityManager();
            if (!$entityManager instanceof EntityManagerInterface) {
                throw new \LogicException('Entity manager not available');
            }

            $entity = $this->find($id);
            if (!$entity) {
                throw new EntityNotFoundException(sprintf('Entity with ID %s not found', $id));
            }

            $entity = $this->updateEntityFromDto($entity, $dto);
            $entityManager->flush();

            return $dataRequest->createResult(static::mapToDto($entity));
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
     * Update an entity from a DTO.
     *
     * @param AdminEntityInterface $entity The entity to update
     * @param AdminApiDto          $dto    The data to update the entity with
     *
     * @return AdminEntityInterface The updated entity
     */
    abstract public function updateEntityFromDto(AdminEntityInterface $entity, AdminApiDto $dto): AdminEntityInterface;
}
