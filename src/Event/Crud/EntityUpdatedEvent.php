<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Event\Crud;

use Freema\ReactAdminApiBundle\Event\ReactAdminApiEvent;
use Freema\ReactAdminApiBundle\Interface\AdminEntityInterface;
use Freema\ReactAdminApiBundle\Request\UpdateDataRequest;
use Freema\ReactAdminApiBundle\Result\UpdateDataResult;
use Symfony\Component\HttpFoundation\Request;

class EntityUpdatedEvent extends ReactAdminApiEvent
{
    public function __construct(
        string $resource,
        Request $request,
        private readonly UpdateDataRequest $requestData,
        private readonly ?AdminEntityInterface $oldEntity,
        private readonly UpdateDataResult $result,
    ) {
        parent::__construct($resource, $request);
    }

    public function getRequestData(): UpdateDataRequest
    {
        return $this->requestData;
    }

    public function getOldEntity(): ?AdminEntityInterface
    {
        return $this->oldEntity;
    }

    public function getResult(): UpdateDataResult
    {
        return $this->result;
    }

    public function getResourceId(): string
    {
        return (string) $this->requestData->getId();
    }
}
