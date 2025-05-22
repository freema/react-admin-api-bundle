<?php

declare(strict_types=1);

namespace Vlp\Mailer\Api\Admin\Dto;

use Vlp\Mailer\Api\Admin\Interface\AdminEntityInterface;
use Vlp\Mailer\Entity\MailerDsn;

class MailerDsnDto extends AdminApiDto
{
    public int $id;

    public int $projectId;

    public string $dsn;

    public bool $trackBounces;

    public string $createdAt;

    public ?array $project;

    public static function getMappedEntityClass(): string
    {
        return MailerDsn::class;
    }

    public static function createFromEntity(AdminEntityInterface|MailerDsn $entity): self
    {
        $dto = new self();
        $dto->id = $entity->getId();
        $dto->projectId = $entity->getProject()->getId();
        $dto->dsn = $entity->getDsn();
        $dto->trackBounces = $entity->trackBounces();
        $dto->createdAt = $entity->getCreatedAt()->format('Y-m-d H:i:s');

        // Add related project data
        $project = $entity->getProject();
        $dto->project = [
            'id' => $project->getId(),
            'name' => $project->getName(),
            'slug' => $project->getSlug(),
        ];

        return $dto;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'projectId' => $this->projectId,
            'dsn' => $this->dsn,
            'trackBounces' => $this->trackBounces,
            'createdAt' => $this->createdAt,
            'project' => $this->project,
        ];
    }
}
