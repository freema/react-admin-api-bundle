<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Dev;

use Doctrine\ORM\EntityManagerInterface;
use Freema\ReactAdminApiBundle\Dev\Entity\User;

class DataFixtures
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function load(): void
    {
        // Vytvoření schéma databáze
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->createSchema($metadata);

        // Přidání testovacích dat
        $user1 = new User();
        $user1->setName('John Doe');
        $user1->setEmail('john@example.com');
        $user1->setRoles(['ROLE_USER']);

        $user2 = new User();
        $user2->setName('Jane Smith');
        $user2->setEmail('jane@example.com');
        $user2->setRoles(['ROLE_ADMIN']);

        $user3 = new User();
        $user3->setName('Bob Wilson');
        $user3->setEmail('bob@example.com');
        $user3->setRoles(['ROLE_USER', 'ROLE_MANAGER']);

        $this->entityManager->persist($user1);
        $this->entityManager->persist($user2);
        $this->entityManager->persist($user3);
        
        $this->entityManager->flush();
    }
}