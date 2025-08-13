# Clean Architecture Integration

This document explains how to integrate ReactAdminApiBundle with Clean Architecture patterns.

## Overview

When using Clean Architecture with strict layer separation, you need to be careful about where to place the React Admin Bundle interfaces. The interfaces should be implemented in the **Infrastructure layer**, not the Domain layer.

## Layer Placement

### ❌ Wrong: Adding interfaces to Domain layer
```php
// src/Domain/Repository/PostRepository.php - DON'T DO THIS
interface PostRepository extends DataRepositoryListInterface  // Wrong!
{
    // Domain methods
}
```

### ✅ Correct: Adding interfaces to Infrastructure layer
```php
// src/Infrastructure/Persistence/Doctrine/Repository/PostRepository.php
class PostRepository extends ServiceEntityRepository implements 
    DataRepositoryListInterface,    // ✅ Correct placement
    DataRepositoryFindInterface,    // ✅ Infrastructure concern
    DataRepositoryCreateInterface,
    DataRepositoryUpdateInterface,
    DataRepositoryDeleteInterface
{
    // Implementation
}
```

## Why Infrastructure Layer?

1. **React Admin Bundle interfaces are framework-specific** - they belong to Infrastructure
2. **Domain layer must remain framework-agnostic** - no external dependencies
3. **Infrastructure implements Domain interfaces** - not the other way around
4. **Admin operations are Infrastructure concern** - not core business logic

## Clean Architecture Example

### Project Structure
```
src/
├── Domain/
│   ├── Model/
│   ├── Repository/          # Domain interfaces only
│   └── Factory/
├── Application/
│   ├── Service/
│   └── Dto/
│       └── Admin/           # Admin DTOs here
└── Infrastructure/
    └── Persistence/
        └── Doctrine/
            └── Repository/  # React Admin interfaces here
```

### Implementation Pattern

1. **Domain Repository Interface** (pure business logic):
```php
// src/Domain/Repository/PostRepository.php
interface PostRepository
{
    public function findByAuthor(Author $author): array;
    public function save(Post $post): void;
    // Only domain methods
}
```

2. **Infrastructure Repository Implementation** (with React Admin):
```php
// src/Infrastructure/Persistence/Doctrine/Repository/PostRepository.php
class PostRepository extends ServiceEntityRepository implements 
    \Discussion\Domain\Repository\PostRepository,  // Domain interface
    DataRepositoryListInterface,                   // React Admin interface
    DataRepositoryFindInterface                    // React Admin interface
{
    use ListTrait;  // React Admin functionality
    
    // Domain methods implementation
    public function findByAuthor(Author $author): array { }
    public function save(Post $post): void { }
    
    // React Admin methods implementation
    public function getFullSearchFields(): array { }
    public static function mapToDto(AdminEntityInterface $entity): AdminApiDto { }
}
```

3. **Service Registration** (services.yaml):
```yaml
services:
    # Domain interface -> Infrastructure implementation
    Discussion\Domain\Repository\PostRepository:
        class: Discussion\Infrastructure\Persistence\Doctrine\Repository\PostRepository
        # React Admin interfaces are automatically available
```

## Common Mistakes

### 1. Creating Separate Admin Repositories
❌ **Don't create separate admin repositories**:
```php
// src/Infrastructure/Persistence/Admin/PostAdminRepository.php - DON'T DO THIS
class PostAdminRepository implements DataRepositoryListInterface { }
```

✅ **Add interfaces to existing Infrastructure repositories**:
```php
// src/Infrastructure/Persistence/Doctrine/Repository/PostRepository.php
class PostRepository implements PostRepositoryInterface, DataRepositoryListInterface { }
```

### 2. Mixing Domain and Infrastructure Concerns
❌ **Don't add React Admin interfaces to Domain**:
```php
// Domain interface with framework dependency - DON'T DO THIS
interface PostRepository extends DataRepositoryListInterface { }
```

✅ **Keep Domain pure, Infrastructure implements both**:
```php
// Domain interface stays clean
interface PostRepository { }

// Infrastructure implements both Domain and React Admin interfaces
class PostRepository implements PostRepositoryInterface, DataRepositoryListInterface { }
```

## Benefits of This Approach

1. **Clean separation of concerns** - Domain remains framework-agnostic
2. **Single repository per entity** - No duplication
3. **Reuse existing business logic** - Admin operations use the same repositories
4. **Maintainable architecture** - Changes in one place affect both API and admin
5. **Consistent data access** - Same validation, mapping, and business rules

## Migration from Wrong Implementation

If you created separate Admin repositories, here's how to fix it:

1. **Delete separate Admin repositories**
2. **Add React Admin interfaces to existing Infrastructure repositories**
3. **Update services.yaml** to remove Admin repository bindings
4. **Move Admin DTOs** to Application/Dto/Admin/ folder
5. **Update DTO getMappedEntityClass()** to return Doctrine entities, not Domain models

This approach maintains Clean Architecture principles while providing full React Admin functionality.