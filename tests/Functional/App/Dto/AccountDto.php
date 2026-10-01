<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Tests\Functional\App\Dto;

use Freema\ReactAdminApiBundle\Dto\AdminApiDto;
use Freema\ReactAdminApiBundle\Interface\AdminEntityInterface;
use Freema\ReactAdminApiBundle\Tests\Functional\App\Entity\Account;

class AccountDto extends AdminApiDto
{
    public ?int $id = null;
    public string $name = '';
    public string $email = '';

    public static function getMappedEntityClass(): string
    {
        return Account::class;
    }

    public static function createFromEntity(AdminEntityInterface $entity): AdminApiDto
    {
        \assert($entity instanceof Account);
        $dto = new self();
        $dto->id = $entity->id;
        $dto->name = $entity->name;
        $dto->email = $entity->email;

        return $dto;
    }

    public function toArray(): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'email' => $this->email];
    }
}
