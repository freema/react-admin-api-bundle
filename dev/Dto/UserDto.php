<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Dev\Dto;

use Freema\ReactAdminApiBundle\Dev\Entity\User;
use Freema\ReactAdminApiBundle\Dto\AdminApiDto;
use Freema\ReactAdminApiBundle\Interface\AdminEntityInterface;
use Symfony\Component\Validator\Constraints as Assert;

class UserDto extends AdminApiDto
{
    public ?int $id = null;
    
    #[Assert\NotBlank(message: 'Name is required')]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: 'Name must be at least {{ limit }} characters long',
        maxMessage: 'Name cannot be longer than {{ limit }} characters'
    )]
    public string $name = '';
    
    #[Assert\NotBlank(message: 'Email is required')]
    #[Assert\Email(message: 'Please provide a valid email address')]
    public string $email = '';
    
    #[Assert\Count(
        min: 1,
        minMessage: 'User must have at least one role'
    )]
    #[Assert\All([
        new Assert\Choice(
            choices: ['ROLE_USER', 'ROLE_ADMIN', 'ROLE_MANAGER'],
            message: 'Invalid role: {{ value }}'
        )
    ])]
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
        $dto->createdAt = $entity->getCreatedAt()?->format('Y-m-d H:i:s');

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