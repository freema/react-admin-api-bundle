# React Admin API Bundle

Symfony bundle pro implementaci API pro [React Admin](https://marmelab.com/react-admin/). Bundle poskytuje základní infrastrukturu pro vytvoření REST API, které je kompatibilní s React Admin Data Provider rozhraním.

## Instalace

```bash
composer require freema/react-admin-api-bundle
```

## Konfigurace

1. Zaregistrujte bundle v `config/bundles.php`:
```php
return [
    // ...
    Freema\ReactAdminApiBundle\ReactAdminApiBundle::class => ['all' => true],
];
```

2. Vytvořte konfiguraci v `config/packages/react_admin_api.yaml`:
```yaml
react_admin_api:
    resources:
        users:
            entity_class: App\Entity\User
            dto_class: App\Dto\UserDto
        products:
            entity_class: App\Entity\Product
            dto_class: App\Dto\ProductDto
```

## Použití

### Entity

Entity musí implementovat `AdminEntityInterface`:

```php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Freema\ReactAdminApiBundle\Interface\AdminEntityInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements AdminEntityInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;
    
    #[ORM\Column(length: 255)]
    private ?string $name = null;
    
    // ...
}
```

Pro entity, které mohou být použity v relacích, implementujte `RelatedEntityInterface`:

```php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Freema\ReactAdminApiBundle\Interface\AdminEntityInterface;
use Freema\ReactAdminApiBundle\Interface\RelatedEntityInterface;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
class Category implements AdminEntityInterface, RelatedEntityInterface
{
    // ...
    
    public function getAlias(): string
    {
        return 'category';
    }
}
```

### DTO

Pro každou entitu vytvořte DTO třídu, která dědí z `AdminApiDto`:

```php
namespace App\Dto;

use App\Entity\User;
use Freema\ReactAdminApiBundle\Dto\AdminApiDto;
use Freema\ReactAdminApiBundle\Interface\AdminEntityInterface;

class UserDto extends AdminApiDto
{
    public ?int $id = null;
    public ?string $name = null;
    
    public static function getMappedEntityClass(): string
    {
        return User::class;
    }
    
    public static function createFromEntity(AdminEntityInterface $entity): AdminApiDto
    {
        if (!$entity instanceof User) {
            throw new \InvalidArgumentException('Entity must be instance of User');
        }
        
        $dto = new self();
        $dto->id = $entity->getId();
        $dto->name = $entity->getName();
        
        return $dto;
    }
    
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }
}
```

### Repository

Repository musí implementovat rozhraní pro CRUD operace:

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
    DataRepositoryListInterface,
    DataRepositoryFindInterface,
    DataRepositoryCreateInterface,
    DataRepositoryUpdateInterface,
    DataRepositoryDeleteInterface
{
    use ListTrait;
    use CreateTrait;
    use UpdateTrait;
    use DeleteTrait;
    
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }
    
    public function getFullSearchFields(): array
    {
        return ['name', 'email'];
    }
    
    public function findWithDto($id): ?AdminApiDto
    {
        $entity = $this->find($id);
        
        if (!$entity) {
            return null;
        }
        
        return UserDto::createFromEntity($entity);
    }
    
    public static function mapToDto(AdminEntityInterface $entity): AdminApiDto
    {
        return UserDto::createFromEntity($entity);
    }
    
    public function createEntitiesFromDto(AdminApiDto $dto): array
    {
        if (!$dto instanceof UserDto) {
            throw new \InvalidArgumentException('DTO must be instance of UserDto');
        }
        
        $user = new User();
        $user->setName($dto->name);
        
        return [$user];
    }
    
    public function updateEntityFromDto(AdminEntityInterface $entity, AdminApiDto $dto): AdminEntityInterface
    {
        if (!$entity instanceof User) {
            throw new \InvalidArgumentException('Entity must be instance of User');
        }
        
        if (!$dto instanceof UserDto) {
            throw new \InvalidArgumentException('DTO must be instance of UserDto');
        }
        
        $entity->setName($dto->name);
        
        return $entity;
    }
}
```

### Frontend (React Admin)

Na straně React Admin vytvořte data provider, který komunikuje s vaším API:

```jsx
import { fetchUtils } from 'react-admin';
import { stringify } from 'query-string';

const apiUrl = '/api';
const httpClient = fetchUtils.fetchJson;

export const dataProvider = {
    getList: (resource, params) => {
        const { page, perPage } = params.pagination;
        const { field, order } = params.sort;
        
        const query = {
            sort: field,
            order: order,
            page: page,
            perPage: perPage,
            filter: JSON.stringify(params.filter),
        };
        
        const url = `${apiUrl}/${resource}?${stringify(query)}`;
        
        return httpClient(url).then(({ json }) => ({
            data: json.data,
            total: json.total,
        }));
    },
    
    getOne: (resource, params) =>
        httpClient(`${apiUrl}/${resource}/${params.id}`).then(({ json }) => ({
            data: json,
        })),
    
    getMany: (resource, params) => {
        const query = {
            filter: JSON.stringify({ id: params.ids }),
        };
        const url = `${apiUrl}/${resource}?${stringify(query)}`;
        return httpClient(url).then(({ json }) => ({ data: json.data }));
    },
    
    getManyReference: (resource, params) => {
        const { page, perPage } = params.pagination;
        const { field, order } = params.sort;
        
        const query = {
            sort: field,
            order: order,
            page: page,
            perPage: perPage,
            filter: JSON.stringify({
                ...params.filter,
                [params.target]: params.id,
            }),
        };
        
        const url = `${apiUrl}/${params.target}/${params.id}/${resource}?${stringify(query)}`;
        
        return httpClient(url).then(({ json }) => ({
            data: json.data,
            total: json.total,
        }));
    },
    
    update: (resource, params) =>
        httpClient(`${apiUrl}/${resource}/${params.id}`, {
            method: 'PUT',
            body: JSON.stringify(params.data),
        }).then(({ json }) => ({ data: json })),
    
    updateMany: (resource, params) => {
        const query = {
            filter: JSON.stringify({ id: params.ids}),
        };
        return httpClient(`${apiUrl}/${resource}?${stringify(query)}`, {
            method: 'PUT',
            body: JSON.stringify(params.data),
        }).then(({ json }) => ({ data: json }));
    },
    
    create: (resource, params) =>
        httpClient(`${apiUrl}/${resource}`, {
            method: 'POST',
            body: JSON.stringify(params.data),
        }).then(({ json }) => ({
            data: { ...params.data, id: json.id },
        })),
    
    delete: (resource, params) =>
        httpClient(`${apiUrl}/${resource}/${params.id}`, {
            method: 'DELETE',
        }).then(() => ({ data: params.previousData })),
    
    deleteMany: (resource, params) =>
        httpClient(`${apiUrl}/${resource}`, {
            method: 'DELETE',
            body: JSON.stringify({ ids: params.ids }),
        }).then(() => ({ data: [] })),
};
```

## Podporované verze

- PHP 8.2+
- Symfony 6.4 / 7.1