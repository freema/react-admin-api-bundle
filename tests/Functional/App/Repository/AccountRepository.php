<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Tests\Functional\App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Freema\ReactAdminApiBundle\CreateTrait;
use Freema\ReactAdminApiBundle\DeleteTrait;
use Freema\ReactAdminApiBundle\Dto\AdminApiDto;
use Freema\ReactAdminApiBundle\FindTrait;
use Freema\ReactAdminApiBundle\Interface\AdminEntityInterface;
use Freema\ReactAdminApiBundle\Interface\DataRepositoryCreateInterface;
use Freema\ReactAdminApiBundle\Interface\DataRepositoryDeleteInterface;
use Freema\ReactAdminApiBundle\Interface\DataRepositoryFindInterface;
use Freema\ReactAdminApiBundle\Interface\DataRepositoryListInterface;
use Freema\ReactAdminApiBundle\Interface\DataRepositoryUpdateInterface;
use Freema\ReactAdminApiBundle\ListTrait;
use Freema\ReactAdminApiBundle\Tests\Functional\App\Dto\AccountDto;
use Freema\ReactAdminApiBundle\Tests\Functional\App\Entity\Account;
use Freema\ReactAdminApiBundle\UpdateTrait;

/**
 * @extends ServiceEntityRepository<Account>
 */
class AccountRepository extends ServiceEntityRepository implements DataRepositoryListInterface, DataRepositoryFindInterface, DataRepositoryCreateInterface, DataRepositoryUpdateInterface, DataRepositoryDeleteInterface
{
    use ListTrait;
    use FindTrait;
    use CreateTrait;
    use UpdateTrait;
    use DeleteTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Account::class);
    }

    public function getFullSearchFields(): array
    {
        return ['name', 'email'];
    }

    public static function mapToDto(AdminEntityInterface $entity): AdminApiDto
    {
        return AccountDto::createFromEntity($entity);
    }

    public function createEntitiesFromDto(AdminApiDto $dto): array
    {
        \assert($dto instanceof AccountDto);
        $account = new Account();
        $account->name = $dto->name;
        $account->email = $dto->email;
        $this->getEntityManager()->persist($account);

        return [$account];
    }

    public function updateEntityFromDto(AdminEntityInterface $entity, AdminApiDto $dto): AdminEntityInterface
    {
        \assert($entity instanceof Account && $dto instanceof AccountDto);
        $entity->name = $dto->name;
        $entity->email = $dto->email;

        return $entity;
    }
}
