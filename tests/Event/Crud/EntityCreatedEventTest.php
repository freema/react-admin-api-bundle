<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Tests\Event\Crud;

use Freema\ReactAdminApiBundle\Event\Crud\EntityCreatedEvent;
use Freema\ReactAdminApiBundle\Request\CreateDataRequest;
use Freema\ReactAdminApiBundle\Result\CreateDataResult;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class EntityCreatedEventTest extends TestCase
{
    public function test_event_creation(): void
    {
        $resource = 'test-resource';
        $request = new Request();
        $requestData = $this->createMock(CreateDataRequest::class);
        $result = $this->createMock(CreateDataResult::class);

        $event = new EntityCreatedEvent($resource, $request, $requestData, $result);

        $this->assertSame($resource, $event->getResource());
        $this->assertSame($request, $event->getRequest());
        $this->assertSame($requestData, $event->getRequestData());
        $this->assertSame($result, $event->getResult());
    }

    public function test_get_request_data(): void
    {
        $requestData = $this->createMock(CreateDataRequest::class);
        $result = $this->createMock(CreateDataResult::class);
        $event = new EntityCreatedEvent('resource', new Request(), $requestData, $result);

        $this->assertSame($requestData, $event->getRequestData());
    }

    public function test_get_result(): void
    {
        $requestData = $this->createMock(CreateDataRequest::class);
        $result = $this->createMock(CreateDataResult::class);
        $event = new EntityCreatedEvent('resource', new Request(), $requestData, $result);

        $this->assertSame($result, $event->getResult());
    }
}
