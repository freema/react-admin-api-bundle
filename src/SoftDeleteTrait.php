<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Freema\ReactAdminApiBundle\Interface\SoftDeletableEntityInterface;
use Freema\ReactAdminApiBundle\Request\DeleteDataRequest;
use Freema\ReactAdminApiBundle\Result\DeleteDataResult;

/**
 * Drop-in alternative to DeleteTrait that performs soft delete when the underlying
 * entity implements SoftDeletableEntityInterface. Falls back to physical remove for
 * everything else so existing repositories keep working when they mix entity kinds.
 *
 * Pair with `applySoftDeleteScope(QueryBuilder)` inside your list query (or rely on
 * SoftDeleteListenerInterface if you wire it via Doctrine's filter API).
 */
trait SoftDeleteTrait
{
    public function delete(DeleteDataRequest $request): DeleteDataResult
    {
        if (!$this instanceof ServiceEntityRepository) {
            throw new \LogicException(sprintf('Trait %s can be used only in a ServiceEntityRepository', self::class));
        }

        $entity = $this->find($request->getId());
        if ($entity === null) {
            return new DeleteDataResult($request->getId());
        }

        $em = $this->getEntityManager();
        if ($entity instanceof SoftDeletableEntityInterface) {
            if ($entity->getDeletedAt() === null) {
                $entity->setDeletedAt(new \DateTimeImmutable());
                $em->flush();
            }
        } else {
            $em->remove($entity);
            $em->flush();
        }

        return new DeleteDataResult($request->getId());
    }

    /**
     * Apply a "WHERE deleted_at IS NULL" predicate when the entity is soft-deletable.
     * Call this from your `list()` override before adding filters so soft-deleted rows
     * never leak through. Skipped when `$includeDeleted` is true.
     */
    protected function applySoftDeleteScope(QueryBuilder $qb, string $alias = 'e', bool $includeDeleted = false): void
    {
        if ($includeDeleted) {
            return;
        }
        $entityClass = $this->getClassName();
        if (!is_subclass_of($entityClass, SoftDeletableEntityInterface::class)) {
            return;
        }
        $qb->andWhere(sprintf('%s.deletedAt IS NULL', $alias));
    }
}
