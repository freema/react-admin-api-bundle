# Installation Guide

This guide covers the complete installation process for the React Admin API Bundle, including both backend and frontend integration.

## Backend Installation

### 1. Install the Bundle

Install the bundle via Composer:

```bash
composer require freema/react-admin-api-bundle
```

### 2. Enable the Bundle

If you're using Symfony Flex, the bundle should be enabled automatically. Otherwise, add it to your `config/bundles.php`:

```php
<?php

return [
    // ... other bundles
    Freema\ReactAdminApiBundle\ReactAdminApiBundle::class => ['all' => true],
];
```

### 3. Configuration (Optional)

The bundle works out of the box with minimal configuration. If you need to customize the behavior, create `config/packages/react_admin_api.yaml`:

```yaml
react_admin_api:
  # Exception handling
  exception_listener:
    enabled: true
    debug_mode: false  # Set to true in dev environment
```

**Note**: The bundle automatically detects data provider types and resources, so explicit configuration is not required.

### 4. Database Setup

Ensure your entities implement the required interfaces:

```php
<?php

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Freema\ReactAdminApiBundle\Interface\DataRepositoryListInterface;
use Freema\ReactAdminApiBundle\Interface\DataRepositoryFindInterface;
use Freema\ReactAdminApiBundle\Interface\DataRepositoryCreateInterface;
use Freema\ReactAdminApiBundle\Interface\DataRepositoryUpdateInterface;
use Freema\ReactAdminApiBundle\Interface\DataRepositoryDeleteInterface;
use Freema\ReactAdminApiBundle\Repository\Trait\ListTrait;
use Freema\ReactAdminApiBundle\Repository\Trait\FindTrait;
use Freema\ReactAdminApiBundle\Repository\Trait\CreateTrait;
use Freema\ReactAdminApiBundle\Repository\Trait\UpdateTrait;
use Freema\ReactAdminApiBundle\Repository\Trait\DeleteTrait;

class UserRepository extends ServiceEntityRepository implements 
    DataRepositoryListInterface,
    DataRepositoryFindInterface,
    DataRepositoryCreateInterface,
    DataRepositoryUpdateInterface,
    DataRepositoryDeleteInterface
{
    use ListTrait;
    use FindTrait;
    use CreateTrait;
    use UpdateTrait;
    use DeleteTrait;
    
    // ... your custom methods
}
```

### 5. Create DTOs

Create DTO classes that implement `DtoInterface`:

```php
<?php

namespace App\Dto;

use Freema\ReactAdminApiBundle\Interface\DtoInterface;

class UserDto implements DtoInterface
{
    public ?int $id = null;
    public ?string $name = null;
    public ?string $email = null;
    public ?bool $active = null;
    public ?\DateTimeInterface $createdAt = null;

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'active' => $this->active,
            'createdAt' => $this->createdAt,
        ];
    }

    public static function getMappedEntityClass(): string
    {
        return 'App\\Entity\\User';
    }

    public static function createFromEntity($entity): self
    {
        $dto = new self();
        $dto->id = $entity->getId();
        $dto->name = $entity->getName();
        $dto->email = $entity->getEmail();
        $dto->active = $entity->isActive();
        $dto->createdAt = $entity->getCreatedAt();
        return $dto;
    }
}
```

### 6. Routing

Add the API routes to your `config/routes.yaml`:

```yaml
react_admin_api:
  resource: "@ReactAdminApiBundle/Resources/config/routing.yaml"
  prefix: /api
```

## Frontend Installation

### Option A: Symfony Asset Mapper (Recommended)

1. **Install the bundle** (already done in backend setup)

2. **Install frontend assets:**
   ```bash
   php bin/console assets:install
   ```

3. **Configure Asset Mapper** in `config/packages/asset_mapper.yaml`:
   ```yaml
   framework:
     asset_mapper:
       paths:
         - assets/
         - vendor/freema/react-admin-api-bundle/assets/dist/
   ```

4. **Import in your JavaScript:**
   ```javascript
   import { createDataProvider } from '@freema/react-admin-api-bundle';
   ```

### Option B: Webpack Encore

1. **Install via npm/yarn:**
   ```bash
   npm install @freema/react-admin-api-bundle
   # or
   yarn add @freema/react-admin-api-bundle
   ```

2. **Configure Webpack Encore** in `webpack.config.js`:
   ```javascript
   const Encore = require('@symfony/webpack-encore');
   
   Encore
     .setOutputPath('public/build/')
     .setPublicPath('/build')
     .addEntry('admin', './assets/admin.js')
     .enableReactPreset()
     .enableTypeScriptLoader()
     // ... other configuration
   ```

3. **Import in your JavaScript:**
   ```javascript
   import { createDataProvider } from '@freema/react-admin-api-bundle';
   ```

### Option C: Manual Installation

1. **Copy assets manually:**
   ```bash
   cp -r vendor/freema/react-admin-api-bundle/assets/dist/* public/js/
   ```

2. **Include in your HTML:**
   ```html
   <script src="/js/data-provider.js"></script>
   ```

## Frontend Setup

### 1. Create React Admin App

```javascript
// assets/admin.js
import React from 'react';
import { createRoot } from 'react-dom/client';
import { Admin, Resource, ListGuesser } from 'react-admin';
import { createDataProvider } from '@freema/react-admin-api-bundle';

const dataProvider = createDataProvider('http://localhost:8080/api');

const App = () => (
  <Admin dataProvider={dataProvider}>
    <Resource name="users" list={ListGuesser} />
    <Resource name="posts" list={ListGuesser} />
  </Admin>
);

const container = document.getElementById('admin-app');
const root = createRoot(container);
root.render(<App />);
```

### 2. Create Template

```twig
{# templates/admin/index.html.twig #}
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Admin Panel</title>
    {{ encore_entry_link_tags('admin') }}
</head>
<body>
    <div id="admin-app"></div>
    {{ encore_entry_script_tags('admin') }}
</body>
</html>
```

### 3. Create Controller

```php
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        return $this->render('admin/index.html.twig');
    }
}
```

## Development Setup

### 1. Start Development Servers

```bash
# Start Symfony development server
symfony server:start

# Start Webpack Encore in watch mode (if using Encore)
npm run watch

# Or start Asset Mapper watch mode
php bin/console asset-map:compile --watch
```

### 2. Development Configuration

```yaml
# config/packages/dev/react_admin_api.yaml
react_admin_api:
  exception_listener:
    debug_mode: true
```

### 3. CORS Configuration (if needed)

```yaml
# config/packages/nelmio_cors.yaml
nelmio_cors:
  defaults:
    origin_regex: true
    allow_origin: ['^http://localhost:[0-9]+$']
    allow_methods: ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS']
    allow_headers: ['Content-Type', 'Authorization', 'X-Requested-With']
    expose_headers: ['Content-Range', 'X-Content-Range']
    max_age: 3600
  paths:
    '^/api/':
      allow_origin: ['*']
      allow_headers: ['*']
      allow_methods: ['*']
      max_age: 3600
```

## Production Setup

### 1. Build Assets

```bash
# For Webpack Encore
npm run build

# For Asset Mapper
php bin/console asset-map:compile
```

### 2. Production Configuration

```yaml
# config/packages/prod/react_admin_api.yaml
react_admin_api:
  exception_listener:
    debug_mode: false
```

### 3. Security Configuration

```yaml
# config/packages/security.yaml
security:
  access_control:
    - { path: ^/api, roles: ROLE_ADMIN }
    - { path: ^/admin, roles: ROLE_ADMIN }
```

## Testing the Installation

### 1. Test API Endpoints

```bash
# Test list endpoint
curl http://localhost:8080/api/users

# Test single resource
curl http://localhost:8080/api/users/1
```

### 2. Test Frontend

1. Visit `http://localhost:8080/admin`
2. Verify that the React Admin interface loads
3. Test CRUD operations

## Troubleshooting

### Common Issues

1. **Bundle not found error**
   - Run `composer dump-autoload`
   - Check bundle is registered in `config/bundles.php`

2. **Assets not loading**
   - Run `php bin/console assets:install`
   - Check asset paths in configuration

3. **CORS errors**
   - Install and configure `nelmio/cors-bundle`
   - Add proper CORS headers

4. **API endpoints not found**
   - Check routing configuration
   - Verify resources are configured correctly

### Debug Commands

```bash
# Check bundle configuration
php bin/console debug:config react_admin_api

# List all routes
php bin/console debug:router

# Check services
php bin/console debug:container react_admin_api

# Clear cache
php bin/console cache:clear
```

## Next Steps

- Read the [Data Providers](data-providers.md) documentation
- Check the [Frontend Integration](frontend-integration.md) guide
- Explore the example implementations in the `/dev` directory