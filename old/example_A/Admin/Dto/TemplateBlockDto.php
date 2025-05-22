<?php

declare(strict_types=1);

namespace Vlp\Mailer\Api\Admin\Dto;

use Vlp\Mailer\Api\Admin\Interface\AdminEntityInterface;
use Vlp\Mailer\Entity\TemplateBlock;

class TemplateBlockDto extends AdminApiDto
{
    public int $id;

    public string $codeName;

    public int $projectId;

    public int $templateRendererId;

    public string $body;

    public bool $active;

    public string $createdAt;

    public static function getMappedEntityClass(): string
    {
        return TemplateBlock::class;
    }

    public static function createFromEntity(AdminEntityInterface|TemplateBlock $entity): self
    {
        $dto = new self();
        $dto->id = $entity->getId();
        $dto->codeName = $entity->getCodeName();
        $dto->projectId = $entity->getProject()->getId();
        $dto->templateRendererId = $entity->getTemplateRenderer()->getId();
        $dto->body = $entity->getBody();
        $dto->active = $entity->isActive();
        $dto->createdAt = $entity->getCreatedAt()->format('Y-m-d H:i:s');

        return $dto;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'codeName' => $this->codeName,
            'projectId' => $this->projectId,
            'templateRendererId' => $this->templateRendererId,
            'body' => $this->body,
            'active' => $this->active,
            'createdAt' => $this->createdAt,
        ];
    }
}
