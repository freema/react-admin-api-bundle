# React Admin API Bundle

Symfony bundle for automatically generating REST API endpoints compatible with [React Admin](https://marmelab.com/react-admin/). The bundle provides a complete infrastructure for creating CRUD APIs with minimal configuration - just create a DTO class and the bundle automatically provides all necessary endpoints.

## Main Features

- ✅ **Automatic endpoint registration** - based only on resource → DTO configuration
- ✅ **CRUD operations** - GET, POST, PUT, DELETE with pagination, sorting, and filtering
- ✅ **React Admin compatibility** - standard response formats
- ✅ **Doctrine integration** - uses standard Symfony/Doctrine patterns
- ✅ **Trait-based repository implementation** - easy implementation of CRUD operations
- ✅ **Type-safe DTO objects** - clean architecture with separation of entity and API layer
- ✅ **Flexible configuration** - supports related resources

## Architecture

The bundle is built on the principle of **resource path → DTO class mapping**. Everything else is derived automatically:

```
Resource path "users" → UserDto::class → User::class (from DTO) → UserRepository (from EntityManager)
```

### Key Components:

1. **DTO (Data Transfer Object)** - defines the API structure and maps to entities
2. **Repository traits** - provide standard CRUD implementations
3. **Resource Configuration Service** - manages resource to DTO mappings
4. **Controllers** - automatically handle HTTP requests

## Installation

```bash
composer require freema/react-admin-api-bundle
```

Register the bundle in `config/bundles.php`:
```php
return [
    // ...
    Freema\ReactAdminApiBundle\ReactAdminApiBundle::class => ['all' => true],
];
```

## Configuration

Create configuration in `config/packages/react_admin_api.yaml`:

```yaml
react_admin_api:
    resources:
        # Simple mapping: resource path => DTO class
        users:
            dto_class: 'App\Dto\UserDto'
        products:
            dto_class: 'App\Dto\ProductDto'
            related_resources:
                categories:
                    dto_class: 'App\Dto\CategoryDto'
                    relationship_method: 'getCategories'
```

## Usage

### 1. Entity

The entity must implement `AdminEntityInterface`:

```php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Freema\ReactAdminApiBundle\Interface\AdminEntityInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
class User implements AdminEntityInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name = '';

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $email = '';

    // getters and setters...
}
```

### 2. DTO (the most important part)

The DTO defines the API structure and is key to the bundle:

```php
namespace App\Dto;

use App\Entity\User;
use Freema\ReactAdminApiBundle\Dto\AdminApiDto;
use Freema\ReactAdminApiBundle\Interface\AdminEntityInterface;

class UserDto extends AdminApiDto
{
    public ?int $id = null;
    public string $name = '';
    public string $email = '';
    public array $roles = [];

    /**
     * Key method - tells the bundle which entity the DTO maps to
     */
    public static function getMappedEntityClass(): string
    {
        return User::class;
    }

    /**
     * Create DTO from entity (for reading)
     */
    public static function createFromEntity(AdminEntityInterface $entity): AdminApiDto
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
     * Convert DTO to array for API response
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'roles' => $this->roles,
        ];
    }
}
```

### 3. Repository

The repository implements CRUD operations using traits:

```php
namespace App\Repository;

use App\Dto\UserDto;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Freema\ReactAdminApiBundle\CreateTrait;
use Freema\ReactAdminApiBundle\DeleteTrait;
use Freema\ReactAdminApiBundle\Dto\AdminApiDto;
use Freema\ReactAdminApiBundle\Interface\AdminEntityInterface;
use Freema\ReactAdminApiBundle\Interface\DataRepositoryCreateInterface;
use Freema\ReactAdminApiBundle\Interface\DataRepositoryDeleteInterface;
use Freema\ReactAdminApiBundle\Interface\DataRepositoryFindInterface;
use Freema\ReactAdminApiBundle\Interface\DataRepositoryListInterface;
use Freema\ReactAdminApiBundle\Interface\DataRepositoryUpdateInterface;
use Freema\ReactAdminApiBundle\ListTrait;
use Freema\ReactAdminApiBundle\UpdateTrait;

class UserRepository extends ServiceEntityRepository implements
    DataRepositoryListInterface,      // for GET /api/users
    DataRepositoryFindInterface,      // for GET /api/users/{id}
    DataRepositoryCreateInterface,    // for POST /api/users
    DataRepositoryUpdateInterface,    // for PUT /api/users/{id}
    DataRepositoryDeleteInterface     // for DELETE /api/users/{id}
{
    use ListTrait;    // implements list() method with pagination, sorting, filtering
    use CreateTrait;  // implements create() method
    use UpdateTrait;  // implements update() method
    use DeleteTrait;  // implements delete() and deleteMany() methods

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Fields for full-text search
     */
    public function getFullSearchFields(): array
    {
        return ['name', 'email'];
    }

    /**
     * Find entity and return as DTO
     */
    public function findWithDto($id): ?AdminApiDto
    {
        $user = $this->find($id);
        return $user ? UserDto::createFromEntity($user) : null;
    }

    /**
     * Map entity to DTO (used by traits)
     */
    public static function mapToDto(AdminEntityInterface $entity): AdminApiDto
    {
        return UserDto::createFromEntity($entity);
    }

    /**
     * Create entities from DTO (used by CreateTrait)
     */
    public function createEntitiesFromDto(AdminApiDto $dto): array
    {
        if (!$dto instanceof UserDto) {
            throw new \InvalidArgumentException('DTO must be instance of UserDto');
        }

        $user = new User();
        $user->setName($dto->name);
        $user->setEmail($dto->email);

        $this->getEntityManager()->persist($user);

        return [$user];
    }

    /**
     * Update entity from DTO (used by UpdateTrait)
     */
    public function updateEntityFromDto(AdminEntityInterface $entity, AdminApiDto $dto): AdminEntityInterface
    {
        if (!$entity instanceof User) {
            throw new \InvalidArgumentException('Entity must be instance of User');
        }

        if (!$dto instanceof UserDto) {
            throw new \InvalidArgumentException('DTO must be instance of UserDto');
        }

        $entity->setName($dto->name);
        $entity->setEmail($dto->email);

        return $entity;
    }
}
```

## Generated Endpoints

After configuration, the bundle automatically creates these endpoints:

| Method | URL | Description |
|--------|-----|-------------|
| GET | `/api/users` | List users with pagination, sorting, filtering |
| GET | `/api/users/{id}` | User detail |
| POST | `/api/users` | Create new user |
| PUT | `/api/users/{id}` | Update user |
| DELETE | `/api/users/{id}` | Delete user |
| DELETE | `/api/users` | Bulk delete (with filter) |

## Request/Response Examples

### GET /api/users?page=1&perPage=10&sort=name&order=ASC
```json
{
    "data": [
        {"id": 1, "name": "John Doe", "email": "john@example.com"},
        {"id": 2, "name": "Jane Smith", "email": "jane@example.com"}
    ],
    "total": 25
}
```

### POST /api/users
Request:
```json
{"name": "New User", "email": "new@example.com"}
```
Response:
```json
{"id": 3, "name": "New User", "email": "new@example.com"}
```

## Development Mode

A dev application is prepared for testing in the `dev/` directory:

```bash
# Run via docker
task dev:up

# Or locally
cd dev && php index.php
```

The dev application uses:
- In-memory SQLite (fast testing)
- Automatic database initialization with test data
- Minimal configuration

## Advanced Features

### Related Resources

```yaml
react_admin_api:
    resources:
        users:
            dto_class: 'App\Dto\UserDto'
            related_resources:
                posts:
                    dto_class: 'App\Dto\PostDto'
                    relationship_method: 'getPosts'
```

Generates endpoint: `GET /api/users/{id}/posts`

### Custom Repository

If you need custom repository logic, just implement the required interfaces:

```php
class CustomUserRepository implements DataRepositoryListInterface
{
    public function list(ListDataRequest $request): ListDataResult
    {
        // Your custom logic
    }
}
```

## Testing

The bundle contains a complete test suite in the `tests/` directory:

```bash
composer test        # PHPUnit tests
composer test:php    # PHP syntax check
composer lint        # Code style check
```

## Supported Versions

- PHP 8.2+
- Symfony 6.4+ / 7.1+
- Doctrine ORM 2.14+

## License

MIT License

## Contributing

Contributions are welcome! Please create an issue or pull request on GitHub.
