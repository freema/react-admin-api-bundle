<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle;

use Doctrine\ORM\EntityManagerInterface;
use Freema\ReactAdminApiBundle\Exception\EntityNotFoundException;
use Freema\ReactAdminApiBundle\Request\DeleteDataRequest;
use Freema\ReactAdminApiBundle\Request\DeleteManyDataRequest;
use Freema\ReactAdminApiBundle\Result\DeleteDataResult;

/**
 * Trait to help implement the DataRepositoryDeleteInterface.
 */
trait DeleteTrait
{
    /**
     * Delete an entity.
     *
     * @throws EntityNotFoundException
     */
    public function delete(DeleteDataRequest $dataRequest): DeleteDataResult
    {
        $id = $dataRequest->getId();
        
        try {
            $entityManager = $this->getEntityManager();
            if (!$entityManager instanceof EntityManagerInterface) {
                throw new \LogicException('Entity manager not available');
            }
            
            $entity = $this->find($id);
            if (!$entity) {
                throw new EntityNotFoundException(sprintf('Entity with ID %s not found', $id));
            }
            
            $entityManager->remove($entity);
            $entityManager->flush();
            
            return $dataRequest->createResult(true);
        } catch (\Exception $e) {
            return $dataRequest->createResult(false, [$e->getMessage()]);
        }
    }
    
    /**
     * Delete multiple entities.
     */
    public function deleteMany(DeleteManyDataRequest $dataRequest): DeleteDataResult
    {
        $ids = $dataRequest->getIds();
        
        try {
            $entityManager = $this->getEntityManager();
            if (!$entityManager instanceof EntityManagerInterface) {
                throw new \LogicException('Entity manager not available');
            }
            
            $entities = $this->findBy(['id' => $ids]);
            
            if (empty($entities)) {
                return $dataRequest->createResult(false, ['No entities found for deletion']);
            }
            
            foreach ($entities as $entity) {
                $entityManager->remove($entity);
            }
            
            $entityManager->flush();
            
            return $dataRequest->createResult(true);
        } catch (\Exception $e) {
            return $dataRequest->createResult(false, [$e->getMessage()]);
        }
    }
    
    /**
     * Get the entity manager.
     */
    abstract public function getEntityManager();
}