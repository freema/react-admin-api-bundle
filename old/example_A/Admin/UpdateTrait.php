<?php

declare(strict_types=1);

namespace Vlp\Mailer\Api\Admin;

use Vlp\Mailer\Api\Admin\Exception\ValidationException;
use Vlp\Mailer\Api\Admin\Request\UpdateDataRequest;
use Vlp\Mailer\Api\Admin\Result\UpdateDataResult;

trait UpdateTrait
{
    public function update(UpdateDataRequest $dataRequest): UpdateDataResult
    {
        $entity = $this->getEntityManager()->getRepository($dataRequest->getAdminApiDto()::getMappedEntityClass())->find($dataRequest->getId());
        if (is_null($entity)) {
            return $dataRequest->createResult(
                status: false,
                errorMessages: ['Entity not found']
            );
        }

        try {
            $entityUpdated = $this->updateEntityFromDto($entity, $dataRequest->getAdminApiDto());
        } catch (ValidationException $e) {
            return $dataRequest->createResult(false, $this::mapToDto($entity));
        }

        try {
            $entityManager = $this->getEntityManager();
            $entityManager->persist($entityUpdated);
            $entityManager->flush();

            return $dataRequest->createResult(true, $this::mapToDto($entityUpdated));
        } catch (\Exception $e) {
            return $dataRequest->createResult(false, $this::mapToDto($entityUpdated));
        }
    }

    abstract public function updateEntityFromDto(mixed $entity, mixed $dto): mixed;
}
