<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Freema\ReactAdminApiBundle\Dto\AdminApiDto;
use Freema\ReactAdminApiBundle\Interface\AdminEntityInterface;

trait FindTrait
{
    public function findWithDto($id): ?AdminApiDto
    {
        if (!$this instanceof ServiceEntityRepository) {
            throw new \LogicException(sprintf('Trait %s can be used only in class extending %s', self::class, ServiceEntityRepository::class));
        }

        $entity = $this->find($id);
        if (!$entity) {
            return null;
        }

        if (!$entity instanceof AdminEntityInterface) {
            throw new \LogicException(sprintf('Entity must implement %s', AdminEntityInterface::class));
        }

        return static::mapToDto($entity);
    }

    abstract public static function mapToDto(AdminEntityInterface $entity): AdminApiDto;
}
