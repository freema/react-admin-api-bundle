# Frontend Integration

This guide explains how to integrate React Admin API Bundle's frontend assets with your React Admin application.

## Installation

### 1. Install the Bundle

First, install the bundle via Composer:

```bash
composer require freema/react-admin-api-bundle
```

### 2. Install Frontend Assets

The bundle includes frontend assets that can be installed using Asset Mapper or Webpack Encore.

#### Option A: Symfony Asset Mapper (Recommended)

```bash
# Install the assets
php bin/console assets:install

# Or use the importmap
php bin/console importmap:require @freema/react-admin-api-bundle
```

#### Option B: Webpack Encore

```bash
# Install via npm/yarn
npm install @freema/react-admin-api-bundle
# or
yarn add @freema/react-admin-api-bundle
```

## Data Provider Usage

### Basic Usage

```javascript
import { createDataProvider } from '@freema/react-admin-api-bundle';

const dataProvider = createDataProvider('http://localhost:8080/api');

// Use in your React Admin app
import { Admin, Resource } from 'react-admin';

const App = () => (
  <Admin dataProvider={dataProvider}>
    <Resource name="users" />
  </Admin>
);
```

### Custom Provider

The bundle provides a custom data provider optimized for the bundle's native API format:

```javascript
import { customDataProvider } from '@freema/react-admin-api-bundle';

const dataProvider = customDataProvider('http://localhost:8080/api');
```

This is equivalent to using `createDataProvider()` which is just a factory function.

### Error Handling

The bundle provides structured error responses for better user experience:

```javascript
// Errors are automatically caught and displayed by React Admin
// No additional configuration needed

// Custom error handling (optional)
const dataProvider = customDataProvider('http://localhost:8080/api');

// Wrap with custom error handler if needed
const enhancedDataProvider = {
  ...dataProvider,
  create: async (resource, params) => {
    try {
      return await dataProvider.create(resource, params);
    } catch (error) {
      // Custom error handling
      console.error('Create failed:', error);
      throw error; // Re-throw for React Admin to handle
    }
  }
};
```

### Data Types

The bundle handles all standard React Admin data types:

```javascript
// Supported data types in your DTOs
const userData = {
  id: 1,                    // number
  name: 'John Doe',         // string
  email: 'john@example.com', // string
  active: true,             // boolean
  roles: ['ROLE_USER'],     // array
  metadata: { key: 'value' }, // object
  createdAt: '2023-01-01',  // string (date)
};
```

## Migration Guide

### From Development Version

If you're currently using the development version from `/dev/admin-app/src/dataProvider.ts`:

1. **Remove the old import:**
   ```javascript
   // Remove this
   import { customDataProvider } from './dataProvider';
   ```

2. **Replace with bundle import:**
   ```javascript
   // Add this
   import { createDataProvider } from '@freema/react-admin-api-bundle';
   
   const dataProvider = createDataProvider('http://localhost:8080/api');
   ```

### From ra-data-simple-rest

You can continue using `ra-data-simple-rest` - the backend automatically detects the format:

```javascript
// Keep your existing code
import simpleRestProvider from 'ra-data-simple-rest';
const dataProvider = simpleRestProvider('http://localhost:8080/api');

// Or migrate to bundle's provider (recommended)
import { createDataProvider } from '@freema/react-admin-api-bundle';
const dataProvider = createDataProvider('http://localhost:8080/api');
```

## TypeScript Support

The bundle includes full TypeScript support:

```typescript
import { 
  createDataProvider, 
  customDataProvider,
  DataProviderFactory 
} from '@freema/react-admin-api-bundle';

// Simple usage
const dataProvider = createDataProvider('http://localhost:8080/api');

// Direct usage
const dataProvider2 = customDataProvider('http://localhost:8080/api');
```

### Type Definitions

```typescript
interface ReactAdminApiOptions {
  apiUrl: string;
}

interface ListResponse<T = any> {
  data: T[];
  total: number;
}

interface GetOneResponse<T = any> {
  data: T;
}

type DataProviderFactory = (apiUrl: string) => DataProvider;
```

## Build Process

### Development

For development, you can use the watch mode:

```bash
cd vendor/freema/react-admin-api-bundle/assets
npm run watch
```

### Production

Assets are automatically built during composer install/update. For manual building:

```bash
cd vendor/freema/react-admin-api-bundle/assets
npm run build
```

## Troubleshooting

### Common Issues

1. **Module not found error**
   - Ensure assets are properly installed
   - Check import paths are correct
   - Verify Symfony asset mapping is configured

2. **CORS errors**
   - Configure CORS headers in Symfony
   - Ensure API URL is correct
   - Check authentication headers

3. **Authentication issues**
   - Verify auth headers are set correctly
   - Check token validity
   - Ensure backend accepts the authentication method

### Debug Mode

The data provider uses standard fetch requests, making it easy to debug in browser dev tools:

```javascript
import { createDataProvider } from '@freema/react-admin-api-bundle';

// Debug logging is handled automatically by React Admin's devtools
const dataProvider = createDataProvider('http://localhost:8080/api');

// For custom debug logging, you can wrap the provider
const debugDataProvider = new Proxy(dataProvider, {
  get(target, prop) {
    return (...args) => {
      console.log(`DataProvider.${prop}`, args);
      return target[prop](...args).then(result => {
        console.log(`DataProvider.${prop} result:`, result);
        return result;
      });
    };
  }
});
```

## Best Practices

1. **Use environment variables for API URLs:**
   ```javascript
   const dataProvider = createDataProvider(
     process.env.REACT_APP_API_URL || 'http://localhost:8080/api'
   );
   ```

2. **Handle authentication gracefully:**
   ```javascript
   // For authentication, use React Admin's built-in authProvider
   // The data provider will automatically include auth headers
   const dataProvider = createDataProvider('http://localhost:8080/api');
   
   // If you need custom authentication handling:
   const authDataProvider = new Proxy(dataProvider, {
     get(target, prop) {
       return async (...args) => {
         try {
           return await target[prop](...args);
         } catch (error) {
           if (error.status === 401) {
             // Handle authentication error
             localStorage.removeItem('auth');
             window.location.href = '/login';
           }
           throw error;
         }
       };
     }
   });
   ```

3. **Cache data provider instance:**
   ```javascript
   // Don't create new instance on every render
   const dataProvider = useMemo(() => 
     createDataProvider('http://localhost:8080/api'), []
   );
   ```

## Examples

### Complete React Admin App

```javascript
import React from 'react';
import { Admin, Resource, ListGuesser } from 'react-admin';
import { createDataProvider } from '@freema/react-admin-api-bundle';

const dataProvider = createDataProvider('http://localhost:8080/api');

const App = () => (
  <Admin dataProvider={dataProvider}>
    <Resource name="users" list={ListGuesser} />
    <Resource name="posts" list={ListGuesser} />
  </Admin>
);

export default App;
```

### With Authentication

```javascript
import React from 'react';
import { Admin, Resource } from 'react-admin';
import { createDataProvider } from '@freema/react-admin-api-bundle';

const dataProvider = createDataProvider('http://localhost:8080/api');

// For authentication, use React Admin's authProvider instead:
const authProvider = {
  login: ({ username, password }) => {
    // Handle login
    localStorage.setItem('token', 'your-token');
    return Promise.resolve();
  },
  logout: () => {
    localStorage.removeItem('token');
    return Promise.resolve();
  },
  checkAuth: () => {
    return localStorage.getItem('token') ? Promise.resolve() : Promise.reject();
  },
  checkError: (error) => {
    return error.status === 401 || error.status === 403
      ? Promise.reject()
      : Promise.resolve();
  },
  getPermissions: () => Promise.resolve(''),
};

const App = () => (
  <Admin dataProvider={dataProvider}>
    <Resource name="users" />
  </Admin>
);

export default App;
```