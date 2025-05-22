<?php
declare(strict_types=1);

namespace Vlp\Mailer\Api\Admin;

use Doctrine\ORM\EntityRepository;
use Vlp\Mailer\Api\Admin\Dto\AdminApiDto;

trait FindTrait
{
    public function findWithDto(string $id): ?AdminApiDto
    {
        if (!$this instanceof EntityRepository) {
            throw new \LogicException(sprintf('Trait %s can be used only in class extending %s', self::class, EntityRepository::class));
        }

        $entity = $this->find($id);
        if (!$entity) {
            return null;
        }

        return $this->mapToDto($entity);
    }

    abstract protected function mapToDto($entity): AdminApiDto;
}
