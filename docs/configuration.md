# ReactAdminApiBundle Configuration

This document explains how to configure the ReactAdminApiBundle for your Symfony application. This bundle provides a bridge between React Admin and your Symfony backend, with a flexible configuration system.

## Key Concepts

The ReactAdminApiBundle is designed around these key concepts:

1. **Resources** - API endpoints exposed through paths (like `/api/users`)
2. **DTOs** - Data Transfer Objects that define what data is exposed through the API
3. **Repositories** - Services that implement the CRUD operations for your resources
4. **Entity mapping** - Each DTO knows which entity class it maps to

The bundle follows a **DTO-centric approach**, where your DTO classes define what entity they map to, rather than configuring this relationship in your bundle configuration.

## Basic Configuration

The bundle is configured in your `config/packages/react_admin_api.yaml` file:

```yaml
react_admin_api:
    # Configure the API route prefix
    routing:
        prefix: '/api'
        load_routes: true
    
    # Define your resources
    resources:
        users:
            dto_class: 'App\Dto\UserDto'
            operations:
                list: true
                get: true
                create: true
                update: true
                delete: true
                delete_many: true
        
        posts:
            dto_class: 'App\Dto\PostDto'
            # You can specify a custom repository service
            repository: 'app.repository.post'
            
            # Define related resources
            related_resources:
                comments:
                    dto_class: 'App\Dto\CommentDto'
```

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

Each resource is defined by its API path and corresponding DTO. The path you specify in the configuration will be used in the URL, combined with the routing prefix:

```yaml
resources:
    # 'users' is the path in the URL: /api/users
    users:
        # The DTO class must implement DtoInterface
        dto_class: 'App\Dto\UserDto'
        
        # Optional custom repository service
        # If not specified, the bundle will get the repository from the entity manager
        # using the entity class defined in the DTO's getMappedEntityClass() method
        repository: 'app.repository.user'
        
        # Configure which operations are available (all true by default)
        operations:
            list: true       # GET /api/users
            get: true        # GET /api/users/{id}
            create: true     # POST /api/users
            update: true     # PUT /api/users/{id}
            delete: true     # DELETE /api/users/{id}
            delete_many: true # DELETE /api/users?id[]=1&id[]=2
```

For React Admin integration, these paths should align with your React Admin resource names. For example:

```jsx
// In your React Admin setup
<Admin dataProvider={dataProvider}>
  <Resource name="users" list={UserList} edit={UserEdit} create={UserCreate} />
  <Resource name="posts" list={PostList} edit={PostEdit} create={PostCreate} />
</Admin>
```

### Related Resources

You can define related resources that will be accessible through nested routes. These are particularly useful for implementing React Admin's reference fields and relationship handling:

```yaml
resources:
    posts:
        dto_class: 'App\Dto\PostDto'
        
        related_resources:
            # This will be accessible at: GET /api/posts/{id}/comments
            # Path becomes part of the URL
            comments:
                dto_class: 'App\Dto\CommentDto'
                # Optional custom repository for this related resource
                repository: 'app.repository.comment'
```

To use related resources in React Admin, you would typically:

```jsx
// In your React Admin component
import { ReferenceField, ReferenceManyField } from 'react-admin';

// For a one-to-many relationship
export const PostShow = () => (
    <Show>
        <SimpleShowLayout>
            <TextField source="title" />
            <ReferenceManyField reference="comments" target="post_id" label="Comments">
                <Datagrid>
                    <TextField source="content" />
                    <DateField source="created_at" />
                </Datagrid>
            </ReferenceManyField>
        </SimpleShowLayout>
    </Show>
);
```

The bundle's related resources endpoint automatically handles filtering the related entities based on their relationship to the parent entity.

### Repository Manager Configuration

By default, the bundle uses Doctrine's EntityManagerInterface to get repositories. However, you can use a custom repository manager if:

1. You're not using Doctrine ORM
2. You have a custom repository factory system
3. You want to use a different persistence layer

Configure your custom repository manager:

```yaml
repository_manager:
    # The service that will be used to get repositories (default: 'doctrine.orm.entity_manager')
    service: 'app.repository_manager'
    
    # Optional factory service to create repositories
    repository_factory: 'app.repository_factory'
```

Your custom repository manager service needs to provide a `getRepository(string $className)` method that returns a repository implementing the appropriate interfaces for the operations you want to support:

- `DataRepositoryListInterface` - For listing resources (GET /resource)
- `DataRepositoryFindInterface` - For finding single resources (GET /resource/{id})
- `DataRepositoryCreateInterface` - For creating resources (POST /resource)
- `DataRepositoryUpdateInterface` - For updating resources (PUT /resource/{id})
- `DataRepositoryDeleteInterface` - For deleting resources (DELETE /resource/{id})
- `RelatedDataRepositoryListInterface` - For listing related resources (GET /resource/{id}/related)

## DTO Configuration

Your DTO classes must implement `DtoInterface` (or extend `AdminApiDto` which implements this interface). Each DTO knows which entity class it maps to, and how to convert between entities and DTOs:

```php
<?php

namespace App\Dto;

use App\Entity\User;
use Freema\ReactAdminApiBundle\Dto\AdminApiDto;
use Freema\ReactAdminApiBundle\Interface\AdminEntityInterface;

class UserDto extends AdminApiDto
{
    public ?int $id = null;
    public string $name = '';
    public string $email = '';
    
    /**
     * Return the entity class that this DTO maps to.
     * This is used by the bundle to determine which repository to use.
     */
    public static function getMappedEntityClass(): string
    {
        return User::class;
    }
    
    /**
     * Create a DTO from an entity object.
     * Used when returning entity data from the API.
     */
    public static function createFromEntity(AdminEntityInterface $entity): self
    {
        if (!$entity instanceof User) {
            throw new \InvalidArgumentException('Entity must be instance of User');
        }
        
        $dto = new self();
        $dto->id = $entity->getId();
        $dto->name = $entity->getName();
        $dto->email = $entity->getEmail();
        
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
        ];
    }
}
```