# Repository Interfaces

This document describes the repository interfaces used by the ReactAdminApiBundle and how to implement them.

## Overview

The bundle uses repositories to perform CRUD operations on your data. These repositories must implement specific interfaces depending on which operations you want to support:

| Interface | Purpose | API Endpoint |
|-----------|---------|--------------|
| `DataRepositoryListInterface` | Listing resources with filtering and pagination | GET /api/resources |
| `DataRepositoryFindInterface` | Finding a single resource by ID | GET /api/resources/{id} |
| `DataRepositoryCreateInterface` | Creating new resources | POST /api/resources |
| `DataRepositoryUpdateInterface` | Updating existing resources | PUT /api/resources/{id} |
| `DataRepositoryDeleteInterface` | Deleting resources | DELETE /api/resources/{id} |
| `RelatedDataRepositoryListInterface` | Listing resources related to another resource | GET /api/resources/{id}/related |

Your repositories only need to implement the interfaces corresponding to the operations you want to support. For example, if you don't want to allow deletion, you don't need to implement `DataRepositoryDeleteInterface`.

## Implementation Traits

The bundle provides traits that help implement these interfaces:

- `ListTrait` - Implements `DataRepositoryListInterface`
- `CreateTrait` - Implements `DataRepositoryCreateInterface`
- `UpdateTrait` - Implements `DataRepositoryUpdateInterface`
- `DeleteTrait` - Implements `DataRepositoryDeleteInterface`
- `ListRelatedToTrait` - Implements `RelatedDataRepositoryListInterface`

## Example Repository Implementation

Here's an example of a repository that implements all interfaces:

```php
<?php

namespace App\Repository;

use App\Dto\UserDto;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Freema\ReactAdminApiBundle\CreateTrait;
use Freema\ReactAdminApiBundle\DeleteTrait;
use Freema\ReactAdminApiBundle\Dto\AdminApiDto;
use Freema\ReactAdminApiBundle\Interface\AdminEntityInterface;
use Freema\ReactAdminApiBundle\Interface\DataRepositoryCreateInterface;
use Freema\ReactAdminApiBundle\Interface\DataRepositoryDeleteInterface;
use Freema\ReactAdminApiBundle\Interface\DataRepositoryFindInterface;
use Freema\ReactAdminApiBundle\Interface\DataRepositoryListInterface;
use Freema\ReactAdminApiBundle\Interface\DataRepositoryUpdateInterface;
use Freema\ReactAdminApiBundle\Interface\RelatedDataRepositoryListInterface;
use Freema\ReactAdminApiBundle\Interface\RelatedEntityInterface;
use Freema\ReactAdminApiBundle\ListRelatedToTrait;
use Freema\ReactAdminApiBundle\ListTrait;
use Freema\ReactAdminApiBundle\UpdateTrait;

class UserRepository extends EntityRepository implements 
    DataRepositoryListInterface,
    DataRepositoryFindInterface,
    DataRepositoryCreateInterface,
    DataRepositoryUpdateInterface,
    DataRepositoryDeleteInterface,
    RelatedDataRepositoryListInterface
{
    use ListTrait;
    use CreateTrait;
    use UpdateTrait;
    use DeleteTrait;
    use ListRelatedToTrait {
        ListRelatedToTrait::applyFilters as applyRelatedFilters;
    }
    
    private EntityManagerInterface $entityManager;
    
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        // If using standard Doctrine EntityRepository
        parent::__construct($entityManager, $entityManager->getClassMetadata(User::class));
    }
    
    public function getEntityManager(): ?EntityManagerInterface
    {
        return $this->entityManager;
    }
    
    public function getFullSearchFields(): array
    {
        return ['name', 'email'];
    }
    
    public static function mapToDto(AdminEntityInterface $entity): AdminApiDto
    {
        return UserDto::createFromEntity($entity);
    }
    
    public function findWithDto($id): ?AdminApiDto
    {
        $entity = $this->find($id);
        
        if (!$entity) {
            return null;
        }
        
        return UserDto::createFromEntity($entity);
    }
    
    public function createEntitiesFromDto(AdminApiDto $dto): array
    {
        if (!$dto instanceof UserDto) {
            throw new \InvalidArgumentException('DTO must be instance of UserDto');
        }
        
        $user = new User();
        $user->setName($dto->name);
        $user->setEmail($dto->email);
        $user->setRoles($dto->roles);
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        return [$user];
    }
    
    public function updateEntityFromDto(AdminEntityInterface $entity, AdminApiDto $dto): AdminEntityInterface
    {
        if (!$entity instanceof User) {
            throw new \InvalidArgumentException('Entity must be instance of User');
        }
        
        if (!$dto instanceof UserDto) {
            throw new \InvalidArgumentException('DTO must be instance of UserDto');
        }
        
        $entity->setName($dto->name);
        $entity->setEmail($dto->email);
        $entity->setRoles($dto->roles);
        
        $this->entityManager->flush();
        
        return $entity;
    }
    
    protected function applyRelationFilter(QueryBuilder $qb, RelatedEntityInterface $entity): void
    {
        // Filter logic depends on how entities are related
        // Example: filter posts by author
        $qb->andWhere('e.author = :entity')
            ->setParameter('entity', $entity);
    }
}
```

## Non-Doctrine Repositories

If you're not using Doctrine ORM, you can still implement these interfaces with your custom persistence layer. The key is to provide the required methods that each interface defines, regardless of how you store data.

For example, a repository using a different persistence layer might look like:

```php
<?php

namespace App\Repository;

use App\Dto\UserDto;
use App\Entity\User;
use App\Service\CustomDatabase;
use Freema\ReactAdminApiBundle\Interface\AdminEntityInterface;
use Freema\ReactAdminApiBundle\Interface\DataRepositoryFindInterface;
use Freema\ReactAdminApiBundle\Interface\DataRepositoryListInterface;
use Freema\ReactAdminApiBundle\Request\ListDataRequest;
use Freema\ReactAdminApiBundle\Result\ListDataResult;

class CustomUserRepository implements DataRepositoryListInterface, DataRepositoryFindInterface
{
    private CustomDatabase $database;
    
    public function __construct(CustomDatabase $database)
    {
        $this->database = $database;
    }
    
    public function list(ListDataRequest $request): ListDataResult
    {
        // Implement listing logic using your custom database service
        $page = $request->getPage() ?? 1;
        $perPage = $request->getPerPage() ?? 10;
        $offset = ($page - 1) * $perPage;
        
        $filterValues = $request->getFilterValues();
        $conditions = [];
        
        foreach ($filterValues as $field => $value) {
            if ($value) {
                $conditions[$field] = $value;
            }
        }
        
        $total = $this->database->count('users', $conditions);
        $rows = $this->database->select('users', $conditions, $offset, $perPage);
        
        $dtos = [];
        foreach ($rows as $row) {
            $user = new User();
            $user->setId($row['id']);
            $user->setName($row['name']);
            $user->setEmail($row['email']);
            
            $dtos[] = UserDto::createFromEntity($user);
        }
        
        return new ListDataResult($dtos, $total);
    }
    
    public function findWithDto($id): ?AdminApiDto
    {
        $row = $this->database->selectOne('users', ['id' => $id]);
        
        if (!$row) {
            return null;
        }
        
        $user = new User();
        $user->setId($row['id']);
        $user->setName($row['name']);
        $user->setEmail($row['email']);
        
        return UserDto::createFromEntity($user);
    }
    
    public static function mapToDto(AdminEntityInterface $entity): AdminApiDto
    {
        return UserDto::createFromEntity($entity);
    }
}
```