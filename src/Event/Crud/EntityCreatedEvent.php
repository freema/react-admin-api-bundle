<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Event\Crud;

use Freema\ReactAdminApiBundle\Event\ReactAdminApiEvent;
use Freema\ReactAdminApiBundle\Request\CreateDataRequest;
use Freema\ReactAdminApiBundle\Result\CreateDataResult;
use Symfony\Component\HttpFoundation\Request;

class EntityCreatedEvent extends ReactAdminApiEvent
{
    public function __construct(
        string $resource,
        Request $request,
        private readonly CreateDataRequest $requestData,
        private readonly CreateDataResult $result,
    ) {
        parent::__construct($resource, $request);
    }

    public function getRequestData(): CreateDataRequest
    {
        return $this->requestData;
    }

    public function getResult(): CreateDataResult
    {
        return $this->result;
    }
}
