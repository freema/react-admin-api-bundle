# Using ReactAdminApiBundle in Your Symfony Application

## 1. Install the bundle

```bash
composer require freema/react-admin-api-bundle
```

## 2. Register the bundle in your application

In your `config/bundles.php`:

```php
return [
    // ... other bundles
    Freema\ReactAdminApiBundle\ReactAdminApiBundle::class => ['all' => true],
];
```

## 3. Import the routes in your routing configuration

In your `config/routes.yaml` or another routing file:

```yaml
# Import routes from ReactAdminApiBundle
react_admin_api:
    resource: '@ReactAdminApiBundle/Resources/config/routes.yaml'
```

## 4. Configure the bundle

Create a file `config/packages/react_admin_api.yaml`:

```yaml
react_admin_api:
    routing:
        prefix: /api  # Base prefix for all API routes
        load_routes: true  # Whether to load the bundle routes automatically
    
    # Define your API resources
    resources:
        users:  # This will create routes at /api/users
            dto_class: App\Dto\UserDto
            operations:
                list: true
                get: true
                create: true
                update: true
                delete: true
                delete_many: true
            related_resources:
                posts:  # This will create routes at /api/users/{id}/posts
                    dto_class: App\Dto\PostDto
                    
        posts:  # This will create routes at /api/posts
            dto_class: App\Dto\PostDto
            repository: App\Repository\PostRepository  # Optional custom repository
    
    # Optional repository manager configuration
    repository_manager:
        service: doctrine.orm.entity_manager  # Default entity manager
```

## 5. Create your DTOs

Each resource needs a corresponding DTO class that implements `DtoInterface`:

```php
<?php

namespace App\Dto;

use Freema\ReactAdminApiBundle\Interface\DtoInterface;

class UserDto implements DtoInterface
{
    public ?int $id = null;
    public string $username;
    public string $email;
    
    public static function createFromEntity(object $entity): self
    {
        $dto = new self();
        $dto->id = $entity->getId();
        $dto->username = $entity->getUsername();
        $dto->email = $entity->getEmail();
        
        return $dto;
    }
}
```