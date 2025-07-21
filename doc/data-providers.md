# Data Providers

React Admin API Bundle provides a custom data provider optimized for React Admin applications. The bundle automatically handles request/response transformation between React Admin and the API.

## Custom Data Provider

The custom data provider is the main provider that matches the bundle's native API format. It's optimized for performance and provides the most complete feature set.

**Request Format:**
```
GET /api/users?sort_field=name&sort_order=ASC&page=1&per_page=10&filter={"active":true}
```

**Response Format:**
```json
{
  "data": [
    {"id": 1, "name": "John Doe", "email": "john@example.com"},
    {"id": 2, "name": "Jane Smith", "email": "jane@example.com"}
  ],
  "total": 25
}
```

**Features:**
- Full CRUD operations (Create, Read, Update, Delete)
- Advanced filtering and sorting
- Pagination with customizable per-page limits
- Bulk operations support (updateMany, deleteMany)
- Optimized for performance
- Content-Range headers for compatibility

## Simple REST Compatibility

The backend also supports `ra-data-simple-rest` format for compatibility with existing React Admin applications.

**Request Format:**
```
GET /api/users?sort=["name","ASC"]&range=[0,9]&filter={"active":true}
```

**Automatic Detection:**
The backend automatically detects the request format and responds appropriately:
- Requests with `sort` and `range` parameters → Simple REST format
- All other requests → Custom format (default)

## Response Headers

Both providers set appropriate headers for React Admin compatibility:

- `Content-Range`: Standard HTTP header for pagination (e.g., `items 0-9/25`)
- `X-Content-Range`: Custom header with total count for legacy compatibility

## Frontend Integration

### Using the Bundle's Data Provider

The bundle provides frontend assets with the custom data provider:

```javascript
import { createDataProvider } from '@freema/react-admin-api-bundle';

const dataProvider = createDataProvider('http://localhost:8080/api');
```

### Using ra-data-simple-rest

For compatibility with existing applications, you can continue using `ra-data-simple-rest`:

```javascript
import simpleRestProvider from 'ra-data-simple-rest';

const dataProvider = simpleRestProvider('http://localhost:8080/api');
```

The backend will automatically detect and handle both formats.

## Troubleshooting

### Common Issues

1. **"Data is not an array" error**
   - Check that the API endpoint returns data in the expected format
   - Ensure the backend is running and accessible

2. **Pagination not working**
   - Verify Content-Range headers are being set by the backend
   - Check that total count is correctly returned in the response

3. **Filtering not working**
   - Ensure filter parameters are JSON-encoded
   - Check that the backend correctly parses filter parameters

### Debug Mode

Enable debug logging to see API requests and responses:

```yaml
# config/packages/monolog.yaml
monolog:
  handlers:
    react_admin_api:
      type: stream
      path: '%kernel.logs_dir%/react_admin_api.log'
      level: debug
      channels: ['react_admin_api']
```

## Error Handling

The bundle provides comprehensive error handling for better developer experience:

### API Exceptions

All errors are structured and returned as JSON through the `ApiExceptionListener`:

```json
{
  "error": "DTO_CLASS_NOT_FOUND",
  "message": "DTO class 'App\\Dto\\NonExistentDto' does not exist. Please check the class name and make sure it's properly loaded.",
  "code": 500
}
```

### Common Error Types

- `DTO_CLASS_NOT_FOUND` - DTO class doesn't exist
- `DTO_INTERFACE_NOT_IMPLEMENTED` - DTO doesn't implement DtoInterface
- `DTO_CREATION_FAILED` - Error during DTO creation
- `VALIDATION_ERROR` - Data validation failed

### Data Processing

The bundle includes a custom `DtoFactory` that handles:
- Automatic DTO instantiation from request data
- Property mapping with type safety
- Null value handling for nullable properties
- Comprehensive error reporting

## Development

### Building Assets

If you're working on the bundle itself:

```bash
# Install dependencies
task assets:install

# Build assets
task assets:build

# Watch for changes
task assets:watch

# Start development with assets watching
task dev:with-assets
```

### Testing

#### Unit Tests

The bundle includes comprehensive unit tests:

```bash
# Run all tests
docker exec react-admin-api-bundle-app ./vendor/bin/phpunit

# Run specific test
docker exec react-admin-api-bundle-app ./vendor/bin/phpunit tests/Service/DtoFactoryTest.php
```

#### Manual Testing

Test the data provider with your React Admin application:

1. Start the development server: `task dev`
2. Open http://localhost:5174/ in your browser
3. Test CRUD operations (create, read, update, delete)
4. Test filtering and sorting functionality