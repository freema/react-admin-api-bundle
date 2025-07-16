# Usage Example

This document provides a complete example of using the ReactAdminApiBundle in a Symfony application.

## 1. Installation

```bash
composer require freema/react-admin-api-bundle
```

## 2. Bundle Registration

The bundle is automatically registered via Symfony Flex. If not, add to `config/bundles.php`:

```php
return [
    // ... other bundles
    Freema\ReactAdminApiBundle\ReactAdminApiBundle::class => ['all' => true],
];
```

## 3. Routing Configuration

Add to `config/routes.yaml`:

```yaml
react_admin_api:
    resource: "@ReactAdminApiBundle/Resources/config/routing.yaml"
    prefix: /api
```

## 4. Create Your Entity

```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $email = null;

    #[ORM\Column(type: 'boolean')]
    private ?bool $active = true;

    #[ORM\Column(type: 'json')]
    private array $roles = ['ROLE_USER'];

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    // Getters and setters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;
        return $this;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}
```

## 5. Create Your DTO

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
    public array $roles = [];
    public ?\DateTimeInterface $createdAt = null;

    /**
     * Return the entity class that this DTO maps to
     */
    public static function getMappedEntityClass(): string
    {
        return User::class;
    }

    /**
     * Create a DTO from an entity object
     */
    public static function createFromEntity($entity): self
    {
        $dto = new self();
        $dto->id = $entity->getId();
        $dto->name = $entity->getName();
        $dto->email = $entity->getEmail();
        $dto->active = $entity->isActive();
        $dto->roles = $entity->getRoles();
        $dto->createdAt = $entity->getCreatedAt();

        return $dto;
    }

    /**
     * Convert the DTO to an array for JSON serialization
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'active' => $this->active,
            'roles' => $this->roles,
            'createdAt' => $this->createdAt?->format('Y-m-d H:i:s'),
        ];
    }
}
```

## 6. Create Repository (Optional)

If you need custom repository methods, extend the default repository:

```php
<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    // Custom methods can be added here
    public function findActiveUsers(): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.active = :active')
            ->setParameter('active', true)
            ->orderBy('u.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
```

## 7. Frontend Integration

### Install Frontend Assets

```bash
# Install React Admin and the bundle's data provider
npm install react-admin @freema/react-admin-api-bundle
```

### Create React Admin App

```javascript
// assets/admin.js
import React from 'react';
import { createRoot } from 'react-dom/client';
import { Admin, Resource } from 'react-admin';
import { createDataProvider } from '@freema/react-admin-api-bundle';
import { UserList, UserEdit, UserCreate, UserShow } from './users';

const dataProvider = createDataProvider('http://localhost:8080/api');

const App = () => (
  <Admin dataProvider={dataProvider}>
    <Resource 
      name="users" 
      list={UserList} 
      edit={UserEdit} 
      create={UserCreate} 
      show={UserShow} 
    />
  </Admin>
);

const container = document.getElementById('admin-app');
const root = createRoot(container);
root.render(<App />);
```

### Create React Admin Components

```javascript
// assets/users.js
import React from 'react';
import {
  List, Datagrid, TextField, EmailField, BooleanField, ArrayField,
  Edit, Create, Show, SimpleForm, SimpleShowLayout, TextInput,
  CheckboxGroupInput, SingleFieldList, ChipField, DateField,
  EditButton, DeleteButton, ShowButton
} from 'react-admin';

export const UserList = () => (
  <List>
    <Datagrid>
      <TextField source="id" />
      <TextField source="name" />
      <EmailField source="email" />
      <BooleanField source="active" />
      <ArrayField source="roles">
        <SingleFieldList>
          <ChipField source="" />
        </SingleFieldList>
      </ArrayField>
      <DateField source="createdAt" />
      <ShowButton />
      <EditButton />
      <DeleteButton />
    </Datagrid>
  </List>
);

export const UserEdit = () => (
  <Edit>
    <SimpleForm>
      <TextInput source="name" required />
      <TextInput source="email" type="email" required />
      <CheckboxGroupInput source="roles" choices={[
        { id: 'ROLE_USER', name: 'User' },
        { id: 'ROLE_ADMIN', name: 'Admin' },
        { id: 'ROLE_MANAGER', name: 'Manager' },
      ]} />
    </SimpleForm>
  </Edit>
);

export const UserCreate = () => (
  <Create>
    <SimpleForm>
      <TextInput source="name" required />
      <TextInput source="email" type="email" required />
      <CheckboxGroupInput source="roles" choices={[
        { id: 'ROLE_USER', name: 'User' },
        { id: 'ROLE_ADMIN', name: 'Admin' },
        { id: 'ROLE_MANAGER', name: 'Manager' },
      ]} defaultValue={['ROLE_USER']} />
    </SimpleForm>
  </Create>
);

export const UserShow = () => (
  <Show>
    <SimpleShowLayout>
      <TextField source="id" />
      <TextField source="name" />
      <EmailField source="email" />
      <BooleanField source="active" />
      <ArrayField source="roles">
        <SingleFieldList>
          <ChipField source="" />
        </SingleFieldList>
      </ArrayField>
      <DateField source="createdAt" />
    </SimpleShowLayout>
  </Show>
);
```

## 8. Development Setup

### Start Development Server

```bash
# Start Symfony development server
symfony server:start

# Start asset compilation (if using Webpack Encore)
npm run watch
```

### Test API Endpoints

```bash
# Test list endpoint
curl http://localhost:8080/api/users

# Test single resource
curl http://localhost:8080/api/users/1

# Test create (POST)
curl -X POST http://localhost:8080/api/users \
  -H "Content-Type: application/json" \
  -d '{"name": "John Doe", "email": "john@example.com", "roles": ["ROLE_USER"]}'

# Test update (PUT)
curl -X PUT http://localhost:8080/api/users/1 \
  -H "Content-Type: application/json" \
  -d '{"name": "John Smith", "email": "john.smith@example.com"}'

# Test delete
curl -X DELETE http://localhost:8080/api/users/1
```

## 9. Error Handling

The bundle provides structured error responses:

```json
{
  "error": "DTO_CLASS_NOT_FOUND",
  "message": "DTO class 'App\\Dto\\NonExistentDto' does not exist. Please check the class name and make sure it's properly loaded.",
  "code": 500
}
```

## 10. Data Provider Compatibility

The bundle automatically detects and supports both data provider formats:

**Custom Provider** (recommended):
```
GET /api/users?sort_field=name&sort_order=ASC&page=1&per_page=10
```

**Simple REST Provider** (compatibility):
```
GET /api/users?sort=["name","ASC"]&range=[0,9]
```

## 11. Testing

Create unit tests for your DTOs:

```php
<?php

namespace App\Tests\Dto;

use App\Dto\UserDto;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserDtoTest extends TestCase
{
    public function testCreateFromEntity(): void
    {
        $user = new User();
        $user->setName('John Doe');
        $user->setEmail('john@example.com');
        $user->setActive(true);
        $user->setRoles(['ROLE_USER']);

        $dto = UserDto::createFromEntity($user);

        $this->assertEquals('John Doe', $dto->name);
        $this->assertEquals('john@example.com', $dto->email);
        $this->assertTrue($dto->active);
        $this->assertEquals(['ROLE_USER'], $dto->roles);
    }

    public function testToArray(): void
    {
        $dto = new UserDto();
        $dto->id = 1;
        $dto->name = 'John Doe';
        $dto->email = 'john@example.com';
        $dto->active = true;
        $dto->roles = ['ROLE_USER'];

        $array = $dto->toArray();

        $this->assertEquals([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'active' => true,
            'roles' => ['ROLE_USER'],
            'createdAt' => null,
        ], $array);
    }
}
```

This example demonstrates a complete setup with a User entity and DTO, including frontend integration with React Admin.