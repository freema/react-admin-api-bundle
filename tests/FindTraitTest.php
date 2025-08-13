<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Tests;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Freema\ReactAdminApiBundle\Dto\AdminApiDto;
use Freema\ReactAdminApiBundle\FindTrait;
use Freema\ReactAdminApiBundle\Interface\AdminEntityInterface;
use PHPUnit\Framework\TestCase;

class FindTraitTest extends TestCase
{
    public function test_find_with_dto_returns_dto(): void
    {
        $entity = $this->createMock(AdminEntityInterface::class);

        $repository = new class extends ServiceEntityRepository {
            use FindTrait;

            private $entity;

            public function __construct()
            {
                // Skip parent constructor
            }

            public function setTestEntity($entity): void
            {
                $this->entity = $entity;
            }

            public function find($id)
            {
                return $this->entity;
            }

            public static function mapToDto(AdminEntityInterface $entity): AdminApiDto
            {
                return new class extends AdminApiDto {
                    public function getId(): string
                    {
                        return '1';
                    }

                    public function toArray(): array
                    {
                        return [];
                    }
                };
            }
        };

        $repository->setTestEntity($entity);
        $result = $repository->findWithDto('123');

        $this->assertInstanceOf(AdminApiDto::class, $result);
    }

    public function test_find_with_dto_returns_null_when_entity_not_found(): void
    {
        $repository = new class extends ServiceEntityRepository {
            use FindTrait;

            public function __construct()
            {
                // Skip parent constructor
            }

            public function find($id)
            {
                return null;
            }

            public static function mapToDto(AdminEntityInterface $entity): AdminApiDto
            {
                throw new \RuntimeException('Should not be called');
            }
        };

        $result = $repository->findWithDto('123');

        $this->assertNull($result);
    }

    public function test_find_with_dto_throws_exception_when_not_service_entity_repository(): void
    {
        $trait = new class {
            use FindTrait;

            public static function mapToDto(AdminEntityInterface $entity): AdminApiDto
            {
                throw new \RuntimeException('Should not be called');
            }
        };

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Trait FindTrait can be used only in class extending Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository');

        $trait->findWithDto('123');
    }

    public function test_find_with_dto_throws_exception_when_entity_does_not_implement_interface(): void
    {
        $entity = new \stdClass(); // Not implementing AdminEntityInterface

        $repository = new class extends ServiceEntityRepository {
            use FindTrait;

            private $entity;

            public function __construct()
            {
                // Skip parent constructor
            }

            public function setTestEntity($entity): void
            {
                $this->entity = $entity;
            }

            public function find($id)
            {
                return $this->entity;
            }

            public static function mapToDto(AdminEntityInterface $entity): AdminApiDto
            {
                throw new \RuntimeException('Should not be called');
            }
        };

        $repository->setTestEntity($entity);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Entity must implement Freema\ReactAdminApiBundle\Interface\AdminEntityInterface');

        $repository->findWithDto('123');
    }
}
