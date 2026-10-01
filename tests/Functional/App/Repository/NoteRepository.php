<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Tests\Functional\App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Freema\ReactAdminApiBundle\Dto\AdminApiDto;
use Freema\ReactAdminApiBundle\Interface\AdminEntityInterface;
use Freema\ReactAdminApiBundle\Interface\RelatedDataRepositoryListInterface;
use Freema\ReactAdminApiBundle\Interface\RelatedEntityInterface;
use Freema\ReactAdminApiBundle\ListRelatedToTrait;
use Freema\ReactAdminApiBundle\Tests\Functional\App\Dto\NoteDto;
use Freema\ReactAdminApiBundle\Tests\Functional\App\Entity\Note;

/**
 * @extends ServiceEntityRepository<Note>
 */
class NoteRepository extends ServiceEntityRepository implements RelatedDataRepositoryListInterface
{
    use ListRelatedToTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Note::class);
    }

    public function getFullSearchFields(): array
    {
        return ['title'];
    }

    public static function mapToDto(AdminEntityInterface $entity): AdminApiDto
    {
        return NoteDto::createFromEntity($entity);
    }

    protected function applyRelationFilter(QueryBuilder $qb, RelatedEntityInterface $entity): void
    {
        $qb->andWhere('e.account = :parent')->setParameter('parent', $entity);
    }
}
