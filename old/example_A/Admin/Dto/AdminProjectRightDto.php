<?php
declare(strict_types=1);

namespace Vlp\Mailer\Api\Admin\Dto;

use Vlp\Mailer\Api\Admin\Interface\AdminEntityInterface;
use Vlp\Mailer\Entity\AdminProjectRight;

class AdminProjectRightDto extends AdminApiDto
{
    public int $id;

    public int $adminId;

    public int $projectId;

    public array $rights;

    public string $createdAt;

    public ?string $lastUpdatedAt;

    public ?array $admin;

    public ?array $project;

    public static function getMappedEntityClass(): string
    {
        return AdminProjectRight::class;
    }

    public static function createFromEntity(AdminEntityInterface|AdminProjectRight $entity): self
    {
        $dto = new self();
        $dto->id = $entity->getId();
        $dto->adminId = $entity->getAdmin()->getId();
        $dto->projectId = $entity->getProject()->getId();
        $dto->rights = $entity->getRights();
        $dto->createdAt = $entity->getCreatedAt()->format('Y-m-d H:i:s');
        $dto->lastUpdatedAt = $entity->getLastUpdatedAt()?->format('Y-m-d H:i:s');

        // Add related entity data
        $dto->admin = [
            'id' => $entity->getAdmin()->getId(),
            'login' => $entity->getAdmin()->getLogin(),
            'name' => $entity->getAdmin()->getName(),
            'surname' => $entity->getAdmin()->getSurname(),
        ];

        $dto->project = [
            'id' => $entity->getProject()->getId(),
            'name' => $entity->getProject()->getName(),
            'slug' => $entity->getProject()->getSlug(),
        ];

        return $dto;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'adminId' => $this->adminId,
            'projectId' => $this->projectId,
            'rights' => $this->rights,
            'createdAt' => $this->createdAt,
            'lastUpdatedAt' => $this->lastUpdatedAt,
            'admin' => $this->admin,
            'project' => $this->project,
        ];
    }
}
