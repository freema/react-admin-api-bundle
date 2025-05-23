# React Admin Test Application

This is a Vite-based React Admin application for testing the React Admin API Bundle.

## Running the Application

1. Make sure the API is running on http://127.0.0.1:8080
2. Install dependencies: `npm install`
3. Start the development server: `npm run dev`
4. Open http://localhost:5173 in your browser

## Features

- **Users Management**: Full CRUD operations for users
  - List all users with pagination
  - View user details
  - Edit existing users
  - Create new users
  - Delete users

## API Endpoints Tested

- GET /api/users - List users
- GET /api/users/{id} - Get single user
- POST /api/users - Create user
- PUT /api/users/{id} - Update user
- DELETE /api/users/{id} - Delete user

## Testing CRUD Operations

1. **Read**: Navigate to the users list to see all users
2. **Create**: Click "Create" button to add new users
3. **Update**: Click on any user row to edit
4. **Delete**: Use delete button in list or edit view

## Development

Built with:
- React 18
- TypeScript
- Vite
- React Admin
- Material-UI components