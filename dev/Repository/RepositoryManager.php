<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Dev\Repository;

/**
 * Custom repository manager service to show how the bundle can work with non-Doctrine managers.
 */
class RepositoryManager
{
    private array $repositories = [];
    
    public function __construct() 
    {
        // Register repositories for testing
        $this->repositories[UserRepository::ENTITY_CLASS] = new UserRepository();
    }
    
    public function getRepository(string $entityClass)
    {
        if (!isset($this->repositories[$entityClass])) {
            throw new \InvalidArgumentException(sprintf('Repository for entity class "%s" not found', $entityClass));
        }
        
        return $this->repositories[$entityClass];
    }
}