<?php

namespace Vlp\Mailer\Api\Admin\Dto;

use Vlp\Mailer\Api\Admin\Interface\AdminEntityInterface;
use Vlp\Mailer\Entity\Project;

class ProjectDto extends AdminApiDto
{
    public int $id;

    public string $name;

    public string $slug;

    public array $roles;

    public string $apiKey;

    public string $defaultFromEmail;

    public ?string $defaultFromName;

    public ?int $defaultMailerDsnId;

    public string $createdAt;

    public array $slugFilterSuggestions;

    public static function getMappedEntityClass(): string
    {
        return Project::class;
    }

    public static function createFromEntity(AdminEntityInterface|Project $entity): self
    {
        $dto = new self();
        $dto->id = $entity->getId();
        $dto->name = $entity->getName();
        $dto->slug = $entity->getSlug();
        $dto->roles = $entity->getRoles();
        $dto->apiKey = $entity->getApiKey();
        $dto->defaultFromEmail = $entity->getDefaultFromEmail();
        $dto->defaultFromName = $entity->getDefaultFromName();
        $dto->defaultMailerDsnId = $entity->getDefaultMailerDsn()?->getId();
        $dto->slugFilterSuggestions = $entity->getSlugFilterSuggestions();

        $dto->createdAt = $entity->getCreatedAt()->format('Y-m-d H:i:s');

        return $dto;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'roles' => $this->roles,
            'apiKey' => $this->apiKey,
            'defaultFromEmail' => $this->defaultFromEmail,
            'defaultFromName' => $this->defaultFromName,
            'defaultMailerDsnId' => $this->defaultMailerDsnId,
            'createdAt' => $this->createdAt,
            'slugFilterSuggestions' => $this->slugFilterSuggestions,
        ];
    }
}
