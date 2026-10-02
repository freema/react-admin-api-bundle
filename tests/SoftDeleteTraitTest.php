<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Tests;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Freema\ReactAdminApiBundle\Interface\AdminEntityInterface;
use Freema\ReactAdminApiBundle\Interface\SoftDeletableEntityInterface;
use Freema\ReactAdminApiBundle\Request\DeleteDataRequest;
use Freema\ReactAdminApiBundle\SoftDeleteTrait;
use PHPUnit\Framework\TestCase;

class SoftDeleteTraitTest extends TestCase
{
    public function test_a_soft_deletable_entity_is_marked_deleted_not_removed(): void
    {
        $entity = $this->softDeletableEntity();
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('remove');
        $em->expects($this->once())->method('flush');

        $result = $this->repository($entity, $em)->delete(new DeleteDataRequest(7));

        $this->assertTrue($result->isSuccess());
        $this->assertNotNull($entity->getDeletedAt());
    }

    public function test_an_already_deleted_entity_is_left_alone(): void
    {
        $deletedAt = new \DateTimeImmutable('2026-01-01');
        $entity = $this->softDeletableEntity();
        $entity->setDeletedAt($deletedAt);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('flush');

        $result = $this->repository($entity, $em)->delete(new DeleteDataRequest(7));

        $this->assertTrue($result->isSuccess());
        $this->assertSame($deletedAt, $entity->getDeletedAt());
    }

    public function test_other_entities_are_removed(): void
    {
        $entity = new class implements AdminEntityInterface {};
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('remove')->with($entity);
        $em->expects($this->once())->method('flush');

        $result = $this->repository($entity, $em)->delete(new DeleteDataRequest(7));

        $this->assertTrue($result->isSuccess());
    }

    public function test_a_missing_entity_is_reported(): void
    {
        $result = $this->repository(null, $this->createMock(EntityManagerInterface::class))->delete(new DeleteDataRequest(7));

        $this->assertFalse($result->isSuccess());
        $this->assertSame(['Entity with ID 7 not found'], $result->getErrorMessages());
    }

    private function softDeletableEntity(): SoftDeletableEntityInterface
    {
        return new class implements SoftDeletableEntityInterface {
            private ?\DateTimeImmutable $deletedAt = null;

            public function getDeletedAt(): ?\DateTimeImmutable
            {
                return $this->deletedAt;
            }

            public function setDeletedAt(?\DateTimeImmutable $deletedAt): void
            {
                $this->deletedAt = $deletedAt;
            }
        };
    }

    /**
     * @return ServiceEntityRepository<object>
     */
    private function repository(?object $entity, EntityManagerInterface $em): ServiceEntityRepository
    {
        return new class($entity, $em) extends ServiceEntityRepository {
            use SoftDeleteTrait;

            public function __construct(
                private readonly ?object $foundEntity,
                private readonly EntityManagerInterface $testEntityManager,
            ) {
            }

            public function find(mixed $id, mixed $lockMode = null, mixed $lockVersion = null): ?object
            {
                return $this->foundEntity;
            }

            protected function getEntityManager(): EntityManagerInterface
            {
                return $this->testEntityManager;
            }
        };
    }
}
