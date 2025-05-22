<?php

declare(strict_types=1);

namespace Vlp\Mailer\Api\Admin\Dto;

use Vlp\Mailer\Api\Admin\Interface\AdminEntityInterface;
use Vlp\Mailer\Entity\Admin;

class AdminDto extends AdminApiDto
{
    public int $id;

    public string $login;

    public ?string $name;

    public ?string $surname;

    public array $roles;

    public string $createdAt;

    public ?string $lastLogin;

    public ?string $avatar;

    public static function getMappedEntityClass(): string
    {
        return Admin::class;
    }

    public static function createFromEntity(AdminEntityInterface|Admin $entity): self
    {
        $dto = new self();
        $dto->id = $entity->getId();
        $dto->login = $entity->getLogin();
        $dto->name = $entity->getName();
        $dto->surname = $entity->getSurname();
        $dto->roles = $entity->getRoles();
        $dto->createdAt = $entity->getCreatedAt()->format('Y-m-d H:i:s');
        $dto->lastLogin = $entity->getLastLogin()?->format('Y-m-d H:i:s');
        $dto->avatar = $entity->getAvatarUrl();

        return $dto;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'login' => $this->login,
            'name' => $this->name,
            'surname' => $this->surname,
            'roles' => $this->roles,
            'createdAt' => $this->createdAt,
            'lastLogin' => $this->lastLogin,
            'avatar' => $this->avatar,
        ];
    }
}
