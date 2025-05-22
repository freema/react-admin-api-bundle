<?php
declare(strict_types=1);

namespace Vlp\Mailer\Api\Admin\Dto;

use Vlp\Mailer\Api\Admin\Interface\AdminEntityInterface;
use Vlp\Mailer\Entity\Campaign;

class CampaignDto extends AdminApiDto
{
    public int $id;

    public string $slug;

    public string $name;

    public string $fromEmail;

    public ?string $fromName;

    public string $subject;

    public ?array $requiredTemplateParams;

    public ?int $mailerDsnId;

    public int $projectId;

    public string $createdAt;

    public static function getMappedEntityClass(): string
    {
        return Campaign::class;
    }

    public static function createFromEntity(AdminEntityInterface|Campaign $entity): self
    {
        $dto = new self();
        $dto->id = $entity->getId();
        $dto->slug = $entity->getSlug();
        $dto->name = $entity->getName();
        $dto->fromEmail = $entity->getFromEmail();
        $dto->fromName = $entity->getFromName();
        $dto->subject = $entity->getSubject();
        $dto->requiredTemplateParams = $entity->getRequiredTemplateParams();
        $dto->mailerDsnId = $entity->getMailerDsn()?->getId();
        $dto->projectId = $entity->getProject()->getId();
        $dto->createdAt = $entity->getCreatedAt()->format('Y-m-d H:i:s');

        return $dto;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'fromEmail' => $this->fromEmail,
            'fromName' => $this->fromName,
            'subject' => $this->subject,
            'requiredTemplateParams' => $this->requiredTemplateParams,
            'mailerDsnId' => $this->mailerDsnId,
            'projectId' => $this->projectId,
            'createdAt' => $this->createdAt,
        ];
    }
}
