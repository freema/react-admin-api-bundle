<?php
declare(strict_types=1);

namespace Vlp\Mailer\Api\Admin\Dto;

use Vlp\Mailer\Api\Admin\Interface\AdminEntityInterface;
use Vlp\Mailer\Entity\TemplateRevision;

class TemplateRevisionDto extends AdminApiDto
{
    public int $id;

    public int $templateId;

    public string $body;

    public bool $active;

    public string $createdAt;

    public array $renderer;

    public array $campaign;

    public ?string $renderedHtml = null;

    public ?string $preview = null;

    public ?array $createdBy = null;

    public static function getMappedEntityClass(): string
    {
        return TemplateRevision::class;
    }

    public static function createFromEntity(AdminEntityInterface|TemplateRevision $entity): self
    {
        $dto = new self();
        $dto->id = $entity->getId();
        $dto->templateId = $entity->getTemplate()->getId();
        $dto->body = $entity->getBody();
        $dto->active = $entity->isActive();
        $dto->createdAt = $entity->getCreatedAt()->format('Y-m-d H:i:s');
        $dto->campaign = [
            'id' => $entity->getTemplate()->getCampaign()->getId(),
            'name' => $entity->getTemplate()->getCampaign()->getName(),
        ];
        $dto->renderer = [
            'id' => $entity->getTemplateRenderer()->getId(),
            'name' => $entity->getTemplateRenderer()->getName(),
            'codeName' => $entity->getTemplateRenderer()->getCodeName(),
        ];
        if ($entity->getCreatedBy()) {
            $dto->createdBy = [
                'id' => $entity->getCreatedBy()->getId(),
                'name' => $entity->getCreatedBy()->getName(),
                'surname' => $entity->getCreatedBy()->getSurname(),
                'login' => $entity->getCreatedBy()->getLogin(),
                'avatarUrl' => $entity->getCreatedBy()->getAvatarUrl(),
            ];
        }

        return $dto;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'templateId' => $this->templateId,
            'body' => $this->body,
            'active' => $this->active,
            'createdAt' => $this->createdAt,
            'renderer' => $this->renderer,
            'campaign' => $this->campaign,
            'renderedHtml' => $this->renderedHtml,
            'preview' => $this->preview,
            'createdBy' => $this->createdBy,
        ];
    }
}
