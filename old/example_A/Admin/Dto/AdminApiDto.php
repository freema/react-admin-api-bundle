<?php
declare(strict_types=1);

namespace Vlp\Mailer\Api\Admin\Dto;

use Vlp\Mailer\Api\Admin\Interface\AdminEntityInterface;

abstract class AdminApiDto
{
    abstract public static function getMappedEntityClass(): string;

    abstract public static function createFromEntity(AdminEntityInterface $entity): AdminApiDto;

    abstract public function toArray(): array;
}
