<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Tests\Functional\App\Dto;

use Freema\ReactAdminApiBundle\Dto\AdminApiDto;
use Freema\ReactAdminApiBundle\Interface\AdminEntityInterface;
use Freema\ReactAdminApiBundle\Tests\Functional\App\Entity\Note;

class NoteDto extends AdminApiDto
{
    public ?int $id = null;
    public string $title = '';

    public static function getMappedEntityClass(): string
    {
        return Note::class;
    }

    public static function createFromEntity(AdminEntityInterface $entity): AdminApiDto
    {
        \assert($entity instanceof Note);
        $dto = new self();
        $dto->id = $entity->id;
        $dto->title = $entity->title;

        return $dto;
    }

    public function toArray(): array
    {
        return ['id' => $this->id, 'title' => $this->title];
    }
}
