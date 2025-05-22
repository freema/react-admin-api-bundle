# React Admin API Bundle

Symfony bundle pro automatické generování REST API endpointů kompatibilních s [React Admin](https://marmelab.com/react-admin/). Bundle poskytuje kompletní infrastrukturu pro vytvoření CRUD API s minimální konfigurací - stačí vytvořit DTO třídu a bundle automaticky poskytne všechny potřebné endpointy.

## Hlavní funkce

- ✅ **Automatická registrace endpointů** - pouze na základě konfigurace resource → DTO
- ✅ **CRUD operace** - GET, POST, PUT, DELETE s paginací, řazením a filtrováním  
- ✅ **React Admin kompatibilita** - standardní response formáty
- ✅ **Doctrine integrace** - využívá standardní Symfony/Doctrine patterns
- ✅ **Trait-based repository implementace** - snadná implementace CRUD operací
- ✅ **Type-safe DTO objekty** - čistá architektura s oddělením entity a API vrstvy
- ✅ **Flexible configuration** - podpora related resources

## Architektura

Bundle je postaven na principu **resource path → DTO class mapping**. Vše ostatní se odvozuje automaticky:

```
Resource path "users" → UserDto::class → User::class (z DTO) → UserRepository (z EntityManager)
```

### Klíčové komponenty:

1. **DTO (Data Transfer Object)** - definuje strukturu API a mapuje na entity
2. **Repository traits** - poskytují standardní CRUD implementace
3. **Resource Configuration Service** - spravuje mapování resources na DTO
4. **Controllery** - automaticky zpracovávají HTTP požadavky

## Instalace

```bash
composer require freema/react-admin-api-bundle
```

Zaregistrujte bundle v `config/bundles.php`:
```php
return [
    // ...
    Freema\ReactAdminApiBundle\ReactAdminApiBundle::class => ['all' => true],
];
```

## Konfigurace

Vytvořte konfiguraci v `config/packages/react_admin_api.yaml`:

```yaml
react_admin_api:
    resources:
        # Jednoduché mapování: resource path => DTO class
        users:
            dto_class: 'App\Dto\UserDto'
        products:
            dto_class: 'App\Dto\ProductDto'
            related_resources:
                categories:
                    dto_class: 'App\Dto\CategoryDto'
                    relationship_method: 'getCategories'
```

## Použití

### 1. Entity

Entity musí implementovat `AdminEntityInterface`:

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
    
    // gettery a settery...
}
```

### 2. DTO (nejdůležitější část)

DTO definuje API strukturu a je klíčové pro bundle:

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
     * Klíčová metoda - říká bundle, na jakou entitu se DTO mapuje
     */
    public static function getMappedEntityClass(): string
    {
        return User::class;
    }
    
    /**
     * Vytvoří DTO z entity (pro čtení)
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
     * Převede DTO na array pro API response
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

Repository implementuje CRUD operace pomocí traits:

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
    DataRepositoryListInterface,      // pro GET /api/users
    DataRepositoryFindInterface,      // pro GET /api/users/{id}
    DataRepositoryCreateInterface,    // pro POST /api/users
    DataRepositoryUpdateInterface,    // pro PUT /api/users/{id}
    DataRepositoryDeleteInterface     // pro DELETE /api/users/{id}
{
    use ListTrait;    // implementuje list() metodu s paginací, řazením, filtrováním
    use CreateTrait;  // implementuje create() metodu
    use UpdateTrait;  // implementuje update() metodu
    use DeleteTrait;  // implementuje delete() a deleteMany() metody
    
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }
    
    /**
     * Pole pro full-text search
     */
    public function getFullSearchFields(): array
    {
        return ['name', 'email'];
    }
    
    /**
     * Najde entitu a vrátí jako DTO
     */
    public function findWithDto($id): ?AdminApiDto
    {
        $user = $this->find($id);
        return $user ? UserDto::createFromEntity($user) : null;
    }
    
    /**
     * Mapuje entitu na DTO (používají traity)
     */
    public static function mapToDto(AdminEntityInterface $entity): AdminApiDto
    {
        return UserDto::createFromEntity($entity);
    }
    
    /**
     * Vytvoří entity z DTO (používá CreateTrait)
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
     * Aktualizuje entitu z DTO (používá UpdateTrait)
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

## Generované endpointy

Po konfiguraci bundle automaticky vytvoří tyto endpointy:

| Metoda | URL | Popis |
|--------|-----|-------|
| GET | `/api/users` | Seznam uživatelů s paginací, řazením, filtrováním |
| GET | `/api/users/{id}` | Detail uživatele |
| POST | `/api/users` | Vytvoření nového uživatele |
| PUT | `/api/users/{id}` | Aktualizace uživatele |
| DELETE | `/api/users/{id}` | Smazání uživatele |
| DELETE | `/api/users` | Hromadné smazání (s filter) |

## Request/Response příklady

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

## Vývojový režim

Pro testování je připravena dev aplikace v `dev/` adresáři:

```bash
# Spuštění přes docker
task dev:up

# Nebo lokálně
cd dev && php index.php
```

Dev aplikace používá:
- SQLite v paměti (rychlé testování)
- Automatickou inicializaci databáze s test daty
- Minimalistickou konfiguraci

## Pokročilé funkce

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

Vygeneruje endpoint: `GET /api/users/{id}/posts`

### Custom Repository

Pokud potřebujete custom repository logiku, stačí implementovat potřebné interfaces:

```php
class CustomUserRepository implements DataRepositoryListInterface
{
    public function list(ListDataRequest $request): ListDataResult
    {
        // Vaše custom logika
    }
}
```

## Testování

Bundle obsahuje kompletní test suite v `tests/` adresáři:

```bash
composer test        # PHPUnit testy
composer test:php    # PHP syntax check
composer lint        # Code style check
```

## Podporované verze

- PHP 8.2+
- Symfony 6.4+ / 7.1+
- Doctrine ORM 2.14+

## License

MIT License

## Příspěvky

Příspěvky jsou vítány! Prosím vytvořte issue nebo pull request na GitHub.