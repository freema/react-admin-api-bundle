<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Tests\Event\Crud;

use Freema\ReactAdminApiBundle\Event\Crud\EntityUpdatedEvent;
use Freema\ReactAdminApiBundle\Interface\AdminEntityInterface;
use Freema\ReactAdminApiBundle\Request\UpdateDataRequest;
use Freema\ReactAdminApiBundle\Result\UpdateDataResult;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class EntityUpdatedEventTest extends TestCase
{
    public function test_event_creation(): void
    {
        $resource = 'test-resource';
        $request = new Request();
        $requestData = $this->createMock(UpdateDataRequest::class);
        $oldEntity = $this->createMock(AdminEntityInterface::class);
        $result = $this->createMock(UpdateDataResult::class);

        $event = new EntityUpdatedEvent($resource, $request, $requestData, $oldEntity, $result);

        $this->assertSame($resource, $event->getResource());
        $this->assertSame($request, $event->getRequest());
        $this->assertSame($requestData, $event->getRequestData());
        $this->assertSame($oldEntity, $event->getOldEntity());
        $this->assertSame($result, $event->getResult());
    }

    public function test_get_request_data(): void
    {
        $requestData = $this->createMock(UpdateDataRequest::class);
        $result = $this->createMock(UpdateDataResult::class);
        $event = new EntityUpdatedEvent('resource', new Request(), $requestData, null, $result);

        $this->assertSame($requestData, $event->getRequestData());
    }

    public function test_get_old_entity(): void
    {
        $requestData = $this->createMock(UpdateDataRequest::class);
        $oldEntity = $this->createMock(AdminEntityInterface::class);
        $result = $this->createMock(UpdateDataResult::class);
        $event = new EntityUpdatedEvent('resource', new Request(), $requestData, $oldEntity, $result);

        $this->assertSame($oldEntity, $event->getOldEntity());
    }

    public function test_get_old_entity_can_be_null(): void
    {
        $requestData = $this->createMock(UpdateDataRequest::class);
        $result = $this->createMock(UpdateDataResult::class);
        $event = new EntityUpdatedEvent('resource', new Request(), $requestData, null, $result);

        $this->assertNull($event->getOldEntity());
    }

    public function test_get_result(): void
    {
        $requestData = $this->createMock(UpdateDataRequest::class);
        $result = $this->createMock(UpdateDataResult::class);
        $event = new EntityUpdatedEvent('resource', new Request(), $requestData, null, $result);

        $this->assertSame($result, $event->getResult());
    }

    public function test_get_resource_id(): void
    {
        $requestData = $this->createMock(UpdateDataRequest::class);
        $requestData->method('getId')->willReturn('123');
        $result = $this->createMock(UpdateDataResult::class);
        $event = new EntityUpdatedEvent('resource', new Request(), $requestData, null, $result);

        $this->assertSame('123', $event->getResourceId());
    }
}
