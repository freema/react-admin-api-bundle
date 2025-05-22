<?php

declare(strict_types=1);

namespace Vlp\Mailer\Api\Admin;

use Vlp\Mailer\Api\Admin\Request\CreateDataRequest;
use Vlp\Mailer\Api\Admin\Result\CreateDataResult;

trait CreateTrait
{
    public function create(CreateDataRequest $dataRequest): CreateDataResult
    {
        try {
            $entities = $this->createEntitiesFromDto($dataRequest->getAdminApiDto());
            foreach ($entities as $entity) {
                $this->getEntityManager()->persist($entity);
            }
            $this->getEntityManager()->flush();

            return $dataRequest->createResult(true);
        } catch (\Exception $e) {
            return $dataRequest->createResult(false, [$e->getMessage()]);
        }
    }

    abstract public function createEntitiesFromDto(mixed $dto): array;
}
