<?php
declare(strict_types=1);

namespace Vlp\Mailer\Api\Admin\Dto;

use Vlp\Mailer\Api\Admin\Interface\AdminEntityInterface;
use Vlp\Mailer\Entity\Template;

class TemplateDto extends AdminApiDto
{
    public int $id;

    public bool $active;

    public array $tags;

    public int $campaignId;

    public array $lastRevision;

    public string $createdAt;

    public ?string $renderedHtml = null;

    public ?string $preview = null;

    public ?array $campaign = null;

    public ?array $project = null;

    public array $attachments = [];

    public static function getMappedEntityClass(): string
    {
        return Template::class;
    }

    public static function createFromEntity(AdminEntityInterface|Template $entity): self
    {
        $dto = new self();
        $dto->id = $entity->getId();
        $dto->active = $entity->isActive();
        $dto->tags = $entity->getTags();
        $dto->campaignId = $entity->getCampaign()->getId();

        $lastRevision = $entity->getLastTemplateRevision();
        if ($lastRevision) {
            $lastRevision->getTemplateRenderer()->getName();
            $dto->lastRevision = [
                'id' => $lastRevision->getId(),
                'body' => $lastRevision->getBody(),
                'active' => $lastRevision->isActive(),
                'createdAt' => $lastRevision->getCreatedAt()->format('Y-m-d H:i:s'),
                'renderer' => [
                    'id' => $lastRevision->getTemplateRenderer()->getId(),
                    'name' => $lastRevision->getTemplateRenderer()->getName(),
                ],
                'createdBy' => null,
            ];

            if ($lastRevision->getCreatedBy()) {
                $dto->lastRevision['createdBy'] = [
                    'id' => $lastRevision->getCreatedBy()->getId(),
                    'login' => $lastRevision->getCreatedBy()->getLogin(),
                    'name' => $lastRevision->getCreatedBy()->getName(),
                    'surname' => $lastRevision->getCreatedBy()->getSurname(),
                    'avatarUrl' => $lastRevision->getCreatedBy()->getAvatarUrl(),
                ];
            }
        }

        $dto->createdAt = $entity->getCreatedAt()->format('Y-m-d H:i:s');

        // Přidáme přílohy
        foreach ($entity->getAttachments() as $attachment) {
            $dto->attachments[] = [
                'id' => $attachment->getId(),
                'name' => $attachment->getName(),
                'type' => $attachment->getType(),
                'active' => $attachment->isActive(),
                'createdAt' => $attachment->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }

        return $dto;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'active' => $this->active,
            'tags' => $this->tags,
            'campaignId' => $this->campaignId,
            'lastRevision' => $this->lastRevision,
            'createdAt' => $this->createdAt,
            'renderedHtml' => $this->renderedHtml,
            'preview' => $this->preview,
            'campaign' => $this->campaign,
            'project' => $this->project,
            'attachments' => $this->attachments,
        ];
    }
}
