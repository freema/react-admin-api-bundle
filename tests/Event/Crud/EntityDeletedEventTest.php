<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Tests\Event\Crud;

use Freema\ReactAdminApiBundle\Event\Crud\EntityDeletedEvent;
use Freema\ReactAdminApiBundle\Interface\AdminEntityInterface;
use Freema\ReactAdminApiBundle\Request\DeleteDataRequest;
use Freema\ReactAdminApiBundle\Result\DeleteDataResult;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class EntityDeletedEventTest extends TestCase
{
    public function test_event_creation(): void
    {
        $resource = 'test-resource';
        $request = new Request();
        $requestData = $this->createMock(DeleteDataRequest::class);
        $deletedEntity = $this->createMock(AdminEntityInterface::class);
        $result = $this->createMock(DeleteDataResult::class);

        $event = new EntityDeletedEvent($resource, $request, $requestData, $deletedEntity, $result);

        $this->assertSame($resource, $event->getResource());
        $this->assertSame($request, $event->getRequest());
        $this->assertSame($requestData, $event->getRequestData());
        $this->assertSame($deletedEntity, $event->getDeletedEntity());
        $this->assertSame($result, $event->getResult());
    }

    public function test_get_request_data(): void
    {
        $requestData = $this->createMock(DeleteDataRequest::class);
        $result = $this->createMock(DeleteDataResult::class);
        $event = new EntityDeletedEvent('resource', new Request(), $requestData, null, $result);

        $this->assertSame($requestData, $event->getRequestData());
    }

    public function test_get_deleted_entity(): void
    {
        $requestData = $this->createMock(DeleteDataRequest::class);
        $deletedEntity = $this->createMock(AdminEntityInterface::class);
        $result = $this->createMock(DeleteDataResult::class);
        $event = new EntityDeletedEvent('resource', new Request(), $requestData, $deletedEntity, $result);

        $this->assertSame($deletedEntity, $event->getDeletedEntity());
    }

    public function test_get_deleted_entity_can_be_null(): void
    {
        $requestData = $this->createMock(DeleteDataRequest::class);
        $result = $this->createMock(DeleteDataResult::class);
        $event = new EntityDeletedEvent('resource', new Request(), $requestData, null, $result);

        $this->assertNull($event->getDeletedEntity());
    }

    public function test_get_result(): void
    {
        $requestData = $this->createMock(DeleteDataRequest::class);
        $result = $this->createMock(DeleteDataResult::class);
        $event = new EntityDeletedEvent('resource', new Request(), $requestData, null, $result);

        $this->assertSame($result, $event->getResult());
    }

    public function test_get_resource_id(): void
    {
        $requestData = $this->createMock(DeleteDataRequest::class);
        $requestData->method('getId')->willReturn('456');
        $result = $this->createMock(DeleteDataResult::class);
        $event = new EntityDeletedEvent('resource', new Request(), $requestData, null, $result);

        $this->assertSame('456', $event->getResourceId());
    }
}
