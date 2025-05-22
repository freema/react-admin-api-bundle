<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Dev\Dto;

use Freema\ReactAdminApiBundle\Dev\Entity\User;
use Freema\ReactAdminApiBundle\Dto\AdminApiDto;
use Freema\ReactAdminApiBundle\Interface\AdminEntityInterface;

class UserDto extends AdminApiDto
{
    public ?int $id = null;
    public string $name = '';
    public string $email = '';
    public array $roles = [];
    public ?string $createdAt = null;

    public static function getMappedEntityClass(): string
    {
        return User::class;
    }

    public static function createFromEntity(AdminEntityInterface $entity): AdminApiDto
    {
        if (!$entity instanceof User) {
            throw new \InvalidArgumentException('Entity must be instance of User');
        }

        $dto = new self();
        $dto->id = $entity->getId();
        $dto->name = $entity->getName();
        $dto->email = $entity->getEmail();
        $dto->roles = $entity->getRoles();
        $dto->createdAt = $entity->getCreatedAt();

        return $dto;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'roles' => $this->roles,
            'createdAt' => $this->createdAt,
        ];
    }
}