<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Event\Crud;

use Freema\ReactAdminApiBundle\Event\ReactAdminApiEvent;
use Freema\ReactAdminApiBundle\Interface\AdminEntityInterface;
use Freema\ReactAdminApiBundle\Request\DeleteDataRequest;
use Freema\ReactAdminApiBundle\Result\DeleteDataResult;
use Symfony\Component\HttpFoundation\Request;

class EntityDeletedEvent extends ReactAdminApiEvent
{
    public function __construct(
        string $resource,
        Request $request,
        private readonly DeleteDataRequest $requestData,
        private readonly ?AdminEntityInterface $deletedEntity,
        private readonly DeleteDataResult $result,
    ) {
        parent::__construct($resource, $request);
    }

    public function getRequestData(): DeleteDataRequest
    {
        return $this->requestData;
    }

    public function getDeletedEntity(): ?AdminEntityInterface
    {
        return $this->deletedEntity;
    }

    public function getResult(): DeleteDataResult
    {
        return $this->result;
    }

    public function getResourceId(): string
    {
        return (string) $this->requestData->getId();
    }
}
