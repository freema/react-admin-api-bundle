<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Dev\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Freema\ReactAdminApiBundle\CreateTrait;
use Freema\ReactAdminApiBundle\DeleteTrait;
use Freema\ReactAdminApiBundle\Dev\Dto\UserDto;
use Freema\ReactAdminApiBundle\Dev\Entity\User;
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

/**
 * In-memory repository for testing purposes.
 */
class UserRepository implements 
    DataRepositoryListInterface,
    DataRepositoryFindInterface,
    DataRepositoryCreateInterface,
    DataRepositoryUpdateInterface,
    DataRepositoryDeleteInterface
{
    public const ENTITY_CLASS = User::class;
    
    use ListTrait;
    use CreateTrait;
    use UpdateTrait;
    use DeleteTrait;
    
    private array $users = [];
    private int $nextId = 1;
    
    public function __construct()
    {
        // Add some demo data
        $user1 = new User();
        $user1->setId(1)->setName('John Doe')->setEmail('john@example.com')->setRoles(['ROLE_USER']);
        
        $user2 = new User();
        $user2->setId(2)->setName('Jane Smith')->setEmail('jane@example.com')->setRoles(['ROLE_ADMIN']);
        
        $this->users = [$user1, $user2];
        $this->nextId = 3;
    }
    
    public function getEntityManager(): ?EntityManagerInterface
    {
        return null;
    }
    
    public function getFullSearchFields(): array
    {
        return ['name', 'email'];
    }
    
    public function createQueryBuilder(string $alias): QueryBuilder
    {
        // Simulovaný QueryBuilder pro ListTrait
        $mockQueryBuilder = new class($this->users) {
            private array $entities;
            private array $conditions = [];
            private array $parameters = [];
            private ?int $firstResult = null;
            private ?int $maxResults = null;
            private ?string $orderBy = null;
            private ?string $orderDirection = null;
            
            public function __construct(array $entities)
            {
                $this->entities = $entities;
            }
            
            public function andWhere(string $condition): self
            {
                $this->conditions[] = $condition;
                return $this;
            }
            
            public function setParameter(string $key, $value): self
            {
                $this->parameters[$key] = $value;
                return $this;
            }
            
            public function setFirstResult(int $firstResult): self
            {
                $this->firstResult = $firstResult;
                return $this;
            }
            
            public function setMaxResults(int $maxResults): self
            {
                $this->maxResults = $maxResults;
                return $this;
            }
            
            public function orderBy(string $sort, string $order): self
            {
                $this->orderBy = $sort;
                $this->orderDirection = $order;
                return $this;
            }
            
            public function select(string $select): self
            {
                return $this;
            }
            
            public function getQuery(): object
            {
                return new class($this->entities, $this->firstResult, $this->maxResults, $this->orderBy, $this->orderDirection) {
                    private array $entities;
                    private ?int $firstResult;
                    private ?int $maxResults;
                    private ?string $orderBy;
                    private ?string $orderDirection;
                    
                    public function __construct(array $entities, ?int $firstResult, ?int $maxResults, ?string $orderBy, ?string $orderDirection)
                    {
                        $this->entities = $entities;
                        $this->firstResult = $firstResult;
                        $this->maxResults = $maxResults;
                        $this->orderBy = $orderBy;
                        $this->orderDirection = $orderDirection;
                    }
                    
                    public function getSingleScalarResult(): int
                    {
                        return count($this->entities);
                    }
                    
                    public function getResult(): array
                    {
                        // Aplikace paginace a řazení
                        $result = $this->entities;
                        
                        // Řazení
                        if ($this->orderBy && $this->orderDirection) {
                            usort($result, function ($a, $b) {
                                $field = substr($this->orderBy, 2); // odstraní "e."
                                $getter = 'get' . ucfirst($field);
                                
                                $valueA = $a->$getter();
                                $valueB = $b->$getter();
                                
                                if ($this->orderDirection === 'ASC') {
                                    return $valueA <=> $valueB;
                                } else {
                                    return $valueB <=> $valueA;
                                }
                            });
                        }
                        
                        // Paginace
                        if ($this->firstResult !== null && $this->maxResults !== null) {
                            $result = array_slice($result, $this->firstResult, $this->maxResults);
                        }
                        
                        return $result;
                    }
                };
            }
        };
        
        return $mockQueryBuilder;
    }
    
    public function find($id): ?User
    {
        foreach ($this->users as $user) {
            if ($user->getId() == $id) {
                return $user;
            }
        }
        
        return null;
    }
    
    public function findWithDto($id): ?AdminApiDto
    {
        $user = $this->find($id);
        
        if (!$user) {
            return null;
        }
        
        return UserDto::createFromEntity($user);
    }
    
    public function findBy(array $criteria): array
    {
        if (isset($criteria['id']) && is_array($criteria['id'])) {
            $result = [];
            foreach ($this->users as $user) {
                if (in_array($user->getId(), $criteria['id'])) {
                    $result[] = $user;
                }
            }
            return $result;
        }
        
        return $this->users;
    }
    
    public static function mapToDto(AdminEntityInterface $entity): AdminApiDto
    {
        return UserDto::createFromEntity($entity);
    }
    
    public function createEntitiesFromDto(AdminApiDto $dto): array
    {
        if (!$dto instanceof UserDto) {
            throw new \InvalidArgumentException('DTO must be instance of UserDto');
        }
        
        $user = new User();
        $user->setId($this->nextId++);
        $user->setName($dto->name);
        $user->setEmail($dto->email);
        $user->setRoles($dto->roles);
        
        $this->users[] = $user;
        
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
        
        return $entity;
    }
    
    protected function applyRelationFilter(QueryBuilder $qb, RelatedEntityInterface $entity): void
    {
        // No-op in this simple example
    }
}