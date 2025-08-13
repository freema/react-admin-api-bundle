# Event System

The ReactAdminApiBundle provides a comprehensive event system that allows you to hook into various stages of request processing and modify the behavior of the bundle.

## Overview

The event system is built on Symfony's EventDispatcher component and provides events for:
- Resource access control
- Data manipulation before and after operations
- Response modification
- Exception handling
- Audit logging and monitoring

## Event Types

### Base Event Class

All events extend `ReactAdminApiEvent` which provides:

```php
abstract class ReactAdminApiEvent extends Event
{
    public function getResource(): string;           // Get resource name
    public function getRequest(): Request;           // Get HTTP request
    public function getContext(): array;             // Get event context
    public function setContext(array $context): self;
    public function addContext(string $key, mixed $value): self;
    public function isCancelled(): bool;             // Check if cancelled
    public function cancel(): self;                  // Cancel operation
    public function getRouteName(): ?string;         // Get route name
    public function getMethod(): string;             // Get HTTP method
    public function getClientIp(): ?string;          // Get client IP
    public function getUserAgent(): ?string;         // Get user agent
}
```

### List Events

#### PreListEvent

Dispatched before data is loaded for list operations. Allows modification of filters, sorting, and pagination.

**Event Name**: `react_admin_api.pre_list`

```php
$event = new PreListEvent($resource, $request, $listDataRequest);

// Modify filters
$event->addFilter('status', 'active');
$event->removeFilter('deleted_at');
$event->setFilters(['category' => 'published']);

// Modify sorting
$event->setSort('created_at', 'DESC');

// Modify pagination
$event->setPagination(1, 50);

// Get current state
$filters = $event->getFilters();
$sort = $event->getSort();
$pagination = $event->getPagination();
```

#### PostListEvent

Dispatched after data is loaded. Allows modification of results.

**Event Name**: `react_admin_api.post_list`

```php
$event = new PostListEvent($resource, $request, $listDataRequest, $listDataResult);

// Modify data
$data = $event->getData();
$event->setData($modifiedData);

// Add/remove items
$event->addItem($newItem);
$event->removeItem(0);

// Filter, map, sort items
$event->filterItems(fn($item) => $item->isActive());
$event->mapItems(fn($item) => $this->enrichItem($item));
$event->sortItems(fn($a, $b) => $a->getName() <=> $b->getName());

// Get statistics
$stats = $event->getStatistics();
```

### CRUD Events

#### EntityCreatedEvent

Dispatched after a new entity is created. Provides access to the created entity data.

**Event Name**: `react_admin_api.entity_created`

```php
use Freema\ReactAdminApiBundle\Event\Crud\EntityCreatedEvent;

$event = new EntityCreatedEvent($resource, $request, $requestData, $result);

// Get the request data
$requestData = $event->getRequestData(); // CreateDataRequest
$dto = $requestData->getDataDto(); // AdminApiDto with data sent by client

// Get the result
$result = $event->getResult(); // CreateDataResult
$createdEntity = $result->getData(); // AdminApiDto|null of created entity
$success = $result->isSuccess(); // bool

// Access entity properties (if created successfully)
if ($createdEntity && $createdEntity->uuid) {
    $entityUuid = $createdEntity->uuid;
}

// Get HTTP request information
$clientIp = $event->getClientIp();
$userAgent = $event->getUserAgent();
$route = $event->getRouteName();
```

#### EntityUpdatedEvent

Dispatched after an entity is updated. Provides access to both old and new entity data.

**Event Name**: `react_admin_api.entity_updated`

```php
use Freema\ReactAdminApiBundle\Event\Crud\EntityUpdatedEvent;

$event = new EntityUpdatedEvent($resource, $request, $requestData, $oldEntity, $result);

// Get the request data
$requestData = $event->getRequestData(); // UpdateDataRequest
$resourceId = $requestData->getId(); // string|int
$newDto = $requestData->getDataDto(); // AdminApiDto with new data

// Get the old entity (before update)
$oldEntity = $event->getOldEntity(); // AdminEntityInterface|null

// Get the result
$result = $event->getResult(); // UpdateDataResult
$updatedEntity = $result->getData(); // AdminApiDto|null of updated entity
$success = $result->isSuccess(); // bool

// Compare old and new values
if ($oldEntity && property_exists($newDto, 'status')) {
    // Example: Check if status changed
    $oldStatus = $oldEntity->getStatus();
    $newStatus = $newDto->status;
    
    if ($oldStatus !== $newStatus) {
        // Status was changed
    }
}
```

#### EntityDeletedEvent

Dispatched after an entity is deleted. Provides access to the deleted entity data.

**Event Name**: `react_admin_api.entity_deleted`

```php
use Freema\ReactAdminApiBundle\Event\Crud\EntityDeletedEvent;

$event = new EntityDeletedEvent($resource, $request, $requestData, $deletedEntity, $result);

// Get the request data
$requestData = $event->getRequestData(); // DeleteDataRequest
$resourceId = $requestData->getId(); // string|int

// Get the deleted entity (captured before deletion)
$deletedEntity = $event->getDeletedEntity(); // AdminEntityInterface|null

// Get the result
$result = $event->getResult(); // DeleteDataResult
$success = $result->isSuccess(); // bool

// Access deleted entity information
if ($deletedEntity) {
    $entityData = $deletedEntity->toArray();
    // Log or process deleted entity data
}
```

### Common Events

#### ResourceAccessEvent

Dispatched on every resource access for access control and auditing.

**Event Name**: `react_admin_api.resource_access`

```php
$event = new ResourceAccessEvent($resource, $request, $operation, $resourceId);

// Get access information
$info = $event->getAccessInfo();
$operation = $event->getOperation();
$resourceId = $event->getResourceId();

// Check operation type
if ($event->isWriteOperation()) {
    // Handle write operations
}

if ($event->isBulkOperation()) {
    // Handle bulk operations
}

// Cancel access
$event->cancel(); // Returns 403 Forbidden
```

#### ResponseEvent

Dispatched before sending the response. Allows modification of headers, status codes, and response data.

**Event Name**: `react_admin_api.response`

```php
$event = new ResponseEvent($resource, $request, $response, $operation, $originalData);

// Modify response data
$data = $event->getResponseData();
$event->setResponseData($modifiedData);
$event->addResponseData('timestamp', time());
$event->removeResponseData('sensitive_field');

// Modify headers
$event->addHeader('X-Custom-Header', 'value');
$event->addCorsHeaders(['https://example.com']);
$event->addCachingHeaders(3600, true);

// Add metadata
$event->addMetadata(['version' => '1.0']);
$event->addTimingInfo($startTime);

// Modify status code
$event->setStatusCode(201);
```

#### ApiExceptionEvent

Dispatched when an exception occurs. Allows custom exception handling.

**Event Name**: `react_admin_api.exception`

```php
$event = new ApiExceptionEvent($resource, $request, $exception, $operation);

// Get exception info
$info = $event->getExceptionInfo();
$exception = $event->getException();

// Check exception type
if ($event->isValidationException()) {
    // Handle validation errors
}

if ($event->isServerError()) {
    // Handle server errors
}

// Set custom response
$response = $event->createErrorResponse('CUSTOM_ERROR', 'Custom message', 422);
$event->setResponse($response);
```

## Creating Event Listeners

### Method 1: Event Subscriber (Recommended)

```php
<?php

namespace App\EventListener;

use Freema\ReactAdminApiBundle\Event\Common\ResourceAccessEvent;
use Freema\ReactAdminApiBundle\Event\List\PreListEvent;
use Freema\ReactAdminApiBundle\Event\List\PostListEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ReactAdminEventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'react_admin_api.resource_access' => 'onResourceAccess',
            'react_admin_api.pre_list' => 'onPreList',
            'react_admin_api.post_list' => 'onPostList',
            'react_admin_api.entity_created' => 'onEntityCreated',
            'react_admin_api.entity_updated' => 'onEntityUpdated',
            'react_admin_api.entity_deleted' => 'onEntityDeleted',
            'react_admin_api.response' => 'onResponse',
        ];
    }

    public function onResourceAccess(ResourceAccessEvent $event): void
    {
        // Log all access attempts
        $this->logger->info('Resource accessed', $event->getAccessInfo());
        
        // Implement access control
        if ($event->getResource() === 'admin_users' && !$this->security->isGranted('ROLE_SUPER_ADMIN')) {
            $event->cancel();
        }
    }

    public function onPreList(PreListEvent $event): void
    {
        // Add default filters
        if ($event->getResource() === 'posts') {
            $event->addFilter('status', 'published');
        }
        
        // Limit results for non-admin users
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            $event->setPagination($event->getPagination()['page'], 10); // Max 10 items
        }
    }

    public function onPostList(PostListEvent $event): void
    {
        // Enrich data with additional information
        if ($event->getResource() === 'users') {
            $event->mapItems(function ($user) {
                return $this->userEnricher->enrich($user);
            });
        }
        
        // Filter sensitive data for non-admin users
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            $event->mapItems(function ($item) {
                return $this->dataSanitizer->sanitize($item);
            });
        }
    }

    public function onEntityCreated(EntityCreatedEvent $event): void
    {
        // Log entity creation
        $entity = $event->getResult()->getData();
        if ($entity) {
            $this->auditLogger->info('Entity created', [
                'resource' => $event->getResource(),
                'id' => $entity->id ?? $entity->uuid ?? 'unknown',
                'user' => $this->security->getUser()?->getUserIdentifier(),
                'data' => $entity->toArray()
            ]);
        }
    }

    public function onEntityUpdated(EntityUpdatedEvent $event): void
    {
        // Log entity updates with changes
        $oldEntity = $event->getOldEntity();
        $newData = $event->getRequestData()->getDataDto();
        
        if ($oldEntity) {
            $changes = $this->detectChanges($oldEntity, $newData);
            
            $this->auditLogger->info('Entity updated', [
                'resource' => $event->getResource(),
                'id' => $event->getResourceId(),
                'user' => $this->security->getUser()?->getUserIdentifier(),
                'changes' => $changes
            ]);
        }
    }

    public function onEntityDeleted(EntityDeletedEvent $event): void
    {
        // Archive deleted entity data
        $deletedEntity = $event->getDeletedEntity();
        
        if ($deletedEntity) {
            $this->auditLogger->warning('Entity deleted', [
                'resource' => $event->getResource(),
                'id' => $event->getResourceId(),
                'user' => $this->security->getUser()?->getUserIdentifier(),
                'archived_data' => $deletedEntity->toArray()
            ]);
        }
    }
}
```

### Method 2: Individual Event Listeners

```php
<?php

namespace App\EventListener;

use Freema\ReactAdminApiBundle\Event\Common\ResourceAccessEvent;

class AuditLogListener
{
    public function __construct(
        private AuditLogger $auditLogger,
        private Security $security
    ) {}

    public function onResourceAccess(ResourceAccessEvent $event): void
    {
        $this->auditLogger->log([
            'resource' => $event->getResource(),
            'operation' => $event->getOperation(),
            'user' => $this->security->getUser()?->getUserIdentifier(),
            'ip' => $event->getClientIp(),
            'timestamp' => new \DateTimeImmutable(),
            'context' => $event->getContext()
        ]);
    }
}
```

**Service configuration:**

```yaml
# config/services.yaml
services:
    App\EventListener\ReactAdminEventSubscriber:
        tags:
            - { name: kernel.event_subscriber }
    
    App\EventListener\AuditLogListener:
        tags:
            - { name: kernel.event_listener, event: react_admin_api.resource_access }
```

## Common Use Cases

### 1. Audit Logging

```php
class AuditLogListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'react_admin_api.resource_access' => 'logAccess',
            'react_admin_api.entity_created' => 'logCreate',
            'react_admin_api.entity_updated' => 'logUpdate',
            'react_admin_api.entity_deleted' => 'logDelete',
            'react_admin_api.response' => 'logResponse',
        ];
    }

    public function logAccess(ResourceAccessEvent $event): void
    {
        $this->auditLogger->info('API Access', [
            'resource' => $event->getResource(),
            'operation' => $event->getOperation(),
            'user' => $this->getCurrentUser(),
            'ip' => $event->getClientIp(),
            'userAgent' => $event->getUserAgent()
        ]);
    }

    public function logResponse(ResponseEvent $event): void
    {
        if ($event->isError()) {
            $this->auditLogger->error('API Error Response', [
                'resource' => $event->getResource(),
                'statusCode' => $event->getStatusCode(),
                'operation' => $event->getOperation()
            ]);
        }
    }
}
```

### 2. Data Enrichment

```php
class DataEnrichmentListener
{
    public function onPostList(PostListEvent $event): void
    {
        if ($event->getResource() === 'users') {
            $event->mapItems(function ($user) {
                // Add computed fields
                $user['displayName'] = $user['firstName'] . ' ' . $user['lastName'];
                $user['avatar'] = $this->avatarService->getAvatarUrl($user['email']);
                $user['lastLoginFormatted'] = $this->formatDate($user['lastLogin']);
                
                return $user;
            });
        }
    }
}
```

### 3. Security Filtering

```php
class SecurityFilterListener
{
    public function onPreList(PreListEvent $event): void
    {
        // Add tenant filter for multi-tenant applications
        if ($this->tenantService->isMultiTenant()) {
            $event->addFilter('tenantId', $this->tenantService->getCurrentTenantId());
        }
        
        // Add user-specific filters
        $user = $this->security->getUser();
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            $event->addFilter('ownerId', $user->getId());
        }
    }

    public function onPostList(PostListEvent $event): void
    {
        // Remove sensitive data based on user permissions
        if (!$this->security->isGranted('ROLE_MANAGER')) {
            $event->mapItems(function ($item) {
                unset($item['salary'], $item['ssn'], $item['internalNotes']);
                return $item;
            });
        }
    }
}
```

### 4. Notification System

```php
class NotificationListener
{
    public function onResourceAccess(ResourceAccessEvent $event): void
    {
        if ($event->isWriteOperation()) {
            $this->notificationService->notify([
                'type' => 'resource_modified',
                'resource' => $event->getResource(),
                'operation' => $event->getOperation(),
                'user' => $this->security->getUser(),
                'timestamp' => new \DateTimeImmutable()
            ]);
        }
    }
}
```

### 5. Moderator Action Logging

```php
class ModeratorActionListener implements EventSubscriberInterface
{
    public function __construct(
        private ModeratorLogService $logService,
        private Security $security,
        private EntityManagerInterface $em
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            'react_admin_api.entity_updated' => 'onEntityUpdated',
            'react_admin_api.entity_deleted' => 'onEntityDeleted',
        ];
    }

    public function onEntityUpdated(EntityUpdatedEvent $event): void
    {
        // Only log moderation actions
        if ($event->getResource() !== 'posts') {
            return;
        }

        $oldEntity = $event->getOldEntity();
        $newData = $event->getRequestData()->getDataDto();
        
        // Check what changed
        if ($oldEntity && property_exists($newData, 'status')) {
            if ($oldEntity->getStatus() !== $newData->status) {
                $this->logService->logModeratorAction(
                    action: 'status_changed',
                    targetType: 'post',
                    targetId: $event->getResourceId(),
                    oldValue: $oldEntity->getStatus(),
                    newValue: $newData->status,
                    moderator: $this->security->getUser(),
                    ip: $event->getClientIp()
                );
            }
        }
    }

    public function onEntityDeleted(EntityDeletedEvent $event): void
    {
        if ($event->getResource() !== 'posts') {
            return;
        }

        $this->logService->logModeratorAction(
            action: 'deleted',
            targetType: 'post',
            targetId: $event->getResourceId(),
            moderator: $this->security->getUser(),
            ip: $event->getClientIp()
        );
    }
}
```

### 6. Caching

```php
class CacheListener
{
    public function onPreList(PreListEvent $event): void
    {
        $cacheKey = $this->generateCacheKey($event);
        
        if ($this->cache->has($cacheKey)) {
            $cachedResult = $this->cache->get($cacheKey);
            // Set cached result and cancel database query
            $event->addContext('cached_result', $cachedResult);
        }
    }

    public function onPostList(PostListEvent $event): void
    {
        if (!$event->getContextValue('cached_result')) {
            $cacheKey = $this->generateCacheKey($event);
            $this->cache->set($cacheKey, $event->getListDataResult(), 300); // 5 minutes
        }
    }

    public function onResponse(ResponseEvent $event): void
    {
        if ($event->getContextValue('cached_result')) {
            $event->addHeader('X-Cache', 'HIT');
        } else {
            $event->addHeader('X-Cache', 'MISS');
        }
    }
}
```

## Best Practices

### 1. Performance Considerations

- **Lightweight Listeners**: Keep event listeners fast and lightweight
- **Async Processing**: Use Symfony Messenger for heavy operations
- **Conditional Logic**: Check conditions early and return quickly
- **Avoid N+1 Queries**: Batch database operations when possible

```php
public function onPostList(PostListEvent $event): void
{
    // Good: Early return for irrelevant resources
    if ($event->getResource() !== 'users') {
        return;
    }
    
    // Good: Batch loading
    $userIds = array_column($event->getData(), 'id');
    $profiles = $this->profileRepository->findByUserIds($userIds);
    
    // Good: Efficient mapping
    $profilesById = array_column($profiles, null, 'userId');
    $event->mapItems(function ($user) use ($profilesById) {
        $user['profile'] = $profilesById[$user['id']] ?? null;
        return $user;
    });
}
```

### 2. Error Handling

```php
public function onPostList(PostListEvent $event): void
{
    try {
        // Your logic here
    } catch (\Exception $e) {
        // Log error but don't break the request
        $this->logger->error('Data enrichment failed', [
            'exception' => $e,
            'resource' => $event->getResource()
        ]);
        
        // Optionally add error context
        $event->addContext('enrichment_error', $e->getMessage());
    }
}
```

### 3. Testing Events

```php
class DataEnrichmentListenerTest extends TestCase
{
    public function testUserDataEnrichment(): void
    {
        $request = new Request();
        $listDataRequest = new ListDataRequest(1, 10, null, null, []);
        $listDataResult = new ListDataResult([
            ['id' => 1, 'firstName' => 'John', 'lastName' => 'Doe']
        ], 1);
        
        $event = new PostListEvent('users', $request, $listDataRequest, $listDataResult);
        
        $listener = new DataEnrichmentListener();
        $listener->onPostList($event);
        
        $data = $event->getData();
        $this->assertEquals('John Doe', $data[0]['displayName']);
    }
}
```

### 4. Priority and Ordering

```php
public static function getSubscribedEvents(): array
{
    return [
        // Higher priority (earlier execution)
        'react_admin_api.pre_list' => ['onPreList', 100],
        'react_admin_api.post_list' => ['onPostList', 50],
        // Lower priority (later execution)
        'react_admin_api.response' => ['onResponse', -100],
    ];
}
```

## Event Reference

| Event Name | Class | When Dispatched | Cancellable |
|------------|-------|-----------------|-------------|
| `react_admin_api.resource_access` | `ResourceAccessEvent` | Before any operation | Yes |
| `react_admin_api.pre_list` | `PreListEvent` | Before loading list data | Yes |
| `react_admin_api.post_list` | `PostListEvent` | After loading list data | No |
| `react_admin_api.entity_created` | `EntityCreatedEvent` | After entity creation | No |
| `react_admin_api.entity_updated` | `EntityUpdatedEvent` | After entity update | No |
| `react_admin_api.entity_deleted` | `EntityDeletedEvent` | After entity deletion | No |
| `react_admin_api.response` | `ResponseEvent` | Before sending response | No |
| `react_admin_api.exception` | `ApiExceptionEvent` | When exception occurs | No |

## Configuration

### Disable Events (for performance)

```yaml
# config/packages/react_admin_api.yaml
react_admin_api:
    events:
        enabled: false  # Disable all events
        # Or disable specific events
        disabled_events:
            - 'react_admin_api.pre_list'
            - 'react_admin_api.post_list'
```

### Event Context

Events support adding custom context data that persists throughout the request:

```php
$event->addContext('startTime', microtime(true));
$event->addContext('userId', $user->getId());
$event->addContext('custom_data', ['key' => 'value']);
```

This context is available in all subsequent events for the same request.