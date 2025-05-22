<?php

declare(strict_types=1);

namespace Vlp\Mailer\Api\Admin\Dto;

use Vlp\Mailer\Api\Admin\Interface\AdminEntityInterface;
use Vlp\Mailer\Entity\TemplateRenderer;

class TemplateRendererDto extends AdminApiDto
{
    public int $id;

    public string $name;

    public string $codeName;

    public string $createdAt;

    public static function getMappedEntityClass(): string
    {
        return TemplateRenderer::class;
    }

    public static function createFromEntity(AdminEntityInterface|TemplateRenderer $entity): self
    {
        $dto = new self();
        $dto->id = $entity->getId();
        $dto->name = $entity->getName();
        $dto->codeName = $entity->getCodeName();
        $dto->createdAt = $entity->getCreatedAt()->format('Y-m-d H:i:s');

        return $dto;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'codeName' => $this->codeName,
            'createdAt' => $this->createdAt,
        ];
    }
}
