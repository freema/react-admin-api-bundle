# Cookie-based authentication

The bundle ships `createCookieAuthProvider`, a ready-to-use `AuthProvider` for react-admin apps that rely on an HttpOnly session cookie set by the Symfony backend (typically via an OAuth callback or a server-side login form).

## Why a helper

React-admin doesn't ship a default cookie auth provider — it expects you to compose one out of `checkAuth` / `getIdentity` / etc. That composition is repetitive and error-prone (forgetting `credentials: 'include'` is the most common production bug). This helper handles the boilerplate.

## Usage

```ts
import { Admin, Resource } from 'react-admin';
import { createCookieAuthProvider, createDataProvider } from '@freema/react-admin-api-bundle';

const authProvider = createCookieAuthProvider({
    meUrl: '/api/me',
    logoutUrl: '/admin/auth/logout',
    loginUrl: '/admin/login',
});
const dataProvider = createDataProvider('/api/admin', { fetchOptions: { credentials: 'include' } });

<Admin authProvider={authProvider} dataProvider={dataProvider}>
    <Resource name="users" />
</Admin>
```

## Options

| Option | Default | Notes |
|---|---|---|
| `meUrl` | `/api/me` | GET endpoint returning the current identity (any envelope; see `parseIdentity`). |
| `loginUrl` | `/login` | Used by the default `onUnauthorized` redirect. |
| `logoutUrl` | `/logout` | POST endpoint that clears the session cookie. |
| `parseIdentity` | unwraps `{success, data}` | Override if your backend uses a different envelope. |
| `onUnauthorized` | `location.href = loginUrl` | Override for SPA-internal navigation. |
| `fetch` | global `fetch` | Inject for tests or to add interceptors. |

## Identity envelope

The default `parseIdentity` accepts either `{ success: true, data: { id, ... } }` (the shape produced by Freema\ReactAdminApiBundle's exception listener / typical Symfony controllers) or a flat `{ id, ... }` payload. It also tolerates `reglogId` / `identityId` / `username` as alternative id keys.

## Permissions

`getPermissions()` returns `data.roles` from the same `meUrl` payload, so RA's `usePermissions()` can drive UI gating (e.g. hide the "Delete" button for non-admins). On error it returns an empty array — never throws.

## What this is NOT

- No token storage. No `localStorage`. Everything lives in the cookie controlled by the server.
- No login form. The bundle assumes login happens on the server (Reglog OAuth, classic form, etc.); the auth provider just observes the resulting cookie.
- No refresh logic. Sessions are server-managed.
