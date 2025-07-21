# ReactAdminApiBundle Configuration

This document explains how to configure the ReactAdminApiBundle for your Symfony application. This bundle provides a bridge between React Admin and your Symfony backend.

## Key Concepts

The ReactAdminApiBundle is designed around these key concepts:

1. **Auto-detection** - The bundle automatically detects data provider formats and handles requests accordingly
2. **DTOs** - Data Transfer Objects that define what data is exposed through the API
3. **DtoFactory** - Service that creates DTOs from request data without requiring Symfony serializer configuration
4. **Error handling** - Comprehensive error handling with structured API responses

The bundle follows a **convention-over-configuration** approach, requiring minimal setup while providing maximum flexibility.

## Basic Configuration

The bundle works out of the box with minimal configuration. Create `config/packages/react_admin_api.yaml` only if you need to customize the default behavior:

```yaml
react_admin_api:
    # Exception handling configuration
    exception_listener:
        enabled: true
        debug_mode: false  # Set to true in development
```

**Note**: The bundle automatically:
- Detects data provider format (custom or simple-rest)
- Maps DTOs to entities via the `getMappedEntityClass()` method
- Handles all CRUD operations through the `ResourceController`
- Provides structured error responses

## Configuration Reference

### Routing Configuration

The `routing` section allows you to configure how the API routes are exposed:

```yaml
routing:
    # The prefix for all API routes (default: '/api')
    prefix: '/api/v1/admin'
    
    # Whether to load the bundle routes automatically (default: true)
    load_routes: true
```

### Resource Configuration

Resources are configured via routing. Add to your `config/routes.yaml`:

```yaml
react_admin_api:
  resource: "@ReactAdminApiBundle/Resources/config/routing.yaml"
  prefix: /api
```

The bundle automatically provides these endpoints for any resource:
- `GET /api/{resource}` - List resources
- `GET /api/{resource}/{id}` - Get single resource
- `POST /api/{resource}` - Create resource
- `PUT /api/{resource}/{id}` - Update resource
- `DELETE /api/{resource}/{id}` - Delete resource

Resources are determined by the DTO class name. For example, `UserDto` creates `/api/users` endpoints.

For React Admin integration, these paths should align with your React Admin resource names. For example:

```jsx
// In your React Admin setup
<Admin dataProvider={dataProvider}>
  <Resource name="users" list={UserList} edit={UserEdit} create={UserCreate} />
  <Resource name="posts" list={PostList} edit={PostEdit} create={PostCreate} />
</Admin>
```

### Error Handling Configuration

The bundle provides comprehensive error handling:

```yaml
react_admin_api:
    exception_listener:
        enabled: true
        debug_mode: true  # Shows detailed error information in development
```

Error responses are structured as:
```json
{
  "error": "DTO_CLASS_NOT_FOUND",
  "message": "DTO class 'App\\Dto\\NonExistentDto' does not exist. Please check the class name and make sure it's properly loaded.",
  "code": 500
}
```

Common error types:
- `DTO_CLASS_NOT_FOUND` - DTO class doesn't exist
- `DTO_INTERFACE_NOT_IMPLEMENTED` - DTO doesn't implement DtoInterface
- `DTO_CREATION_FAILED` - Error during DTO creation
- `VALIDATION_ERROR` - Data validation failed

### Data Provider Detection

The bundle automatically detects the data provider format from the request:

**Custom Provider** (default):
```
GET /api/users?sort_field=name&sort_order=ASC&page=1&per_page=10
```

**Simple REST Provider** (compatibility):
```
GET /api/users?sort=["name","ASC"]&range=[0,9]
```

No configuration needed - the bundle handles both formats automatically.

### DtoFactory Service

The bundle uses a custom `DtoFactory` service to create DTOs from request data, replacing the need for Symfony serializer configuration:

```php
// Automatically handles:
$dto = $dtoFactory->createFromArray($requestData, UserDto::class);
```

This provides:
- Automatic property mapping
- Type safety
- Null value handling
- Comprehensive error messages

## DTO Configuration

Your DTO classes must implement `DtoInterface`. Each DTO knows which entity class it maps to:

```php
<?php

namespace App\Dto;

use App\Entity\User;
use Freema\ReactAdminApiBundle\Interface\DtoInterface;

class UserDto implements DtoInterface
{
    public ?int $id = null;
    public ?string $name = null;
    public ?string $email = null;
    public ?bool $active = null;
    
    /**
     * Return the entity class that this DTO maps to.
     * Used by the bundle to determine which repository to use.
     */
    public static function getMappedEntityClass(): string
    {
        return User::class;
    }
    
    /**
     * Create a DTO from an entity object.
     * Used when returning entity data from the API.
     */
    public static function createFromEntity($entity): self
    {
        $dto = new self();
        $dto->id = $entity->getId();
        $dto->name = $entity->getName();
        $dto->email = $entity->getEmail();
        $dto->active = $entity->isActive();
        
        return $dto;
    }
    
    /**
     * Convert the DTO to an array for JSON serialization.
     * This controls exactly what data is exposed through the API.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'active' => $this->active,
        ];
    }
}
```

## Development vs Production

**Development** (`config/packages/dev/react_admin_api.yaml`):
```yaml
react_admin_api:
    exception_listener:
        debug_mode: true  # Detailed error information
```

**Production** (`config/packages/prod/react_admin_api.yaml`):
```yaml
react_admin_api:
    exception_listener:
        debug_mode: false  # User-friendly error messages
```