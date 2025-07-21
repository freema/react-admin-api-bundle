# React Admin API Bundle Development Environment

This directory contains a minimal Symfony application to test the React Admin API Bundle.

## Running the Development Server

Start the development server:

```bash
docker-compose up -d
docker exec -it react-admin-api-bundle-app php -S 0.0.0.0:8080 -t dev/
```

## Available API Endpoints

The test environment exposes these endpoints:

### Resource endpoints

- GET /api/users - List users
- GET /api/users/{id} - Get a specific user
- POST /api/users - Create a user
- PUT /api/users/{id} - Update a user
- DELETE /api/users/{id} - Delete a user
- DELETE /api/users?id[]={id1}&id[]={id2} - Delete multiple users

### Example Requests

#### List users

```bash
curl -X GET http://localhost:8080/api/users
```

#### Get user by ID

```bash
curl -X GET http://localhost:8080/api/users/1
```

#### Create user

```bash
curl -X POST http://localhost:8080/api/users \
  -H "Content-Type: application/json" \
  -d '{"name":"New User","email":"new@example.com","roles":["ROLE_USER"]}'
```

#### Update user

```bash
curl -X PUT http://localhost:8080/api/users/1 \
  -H "Content-Type: application/json" \
  -d '{"name":"Updated User","email":"updated@example.com","roles":["ROLE_USER"]}'
```

#### Delete user

```bash
curl -X DELETE http://localhost:8080/api/users/1
```

## Configuration

The bundle configuration for testing is defined in:
- `dev/config/packages/react_admin_api.yaml` 
- `dev/DevKernel.php`

This setup demonstrates how an application would integrate the bundle, including
custom resource configuration and endpoint customization.