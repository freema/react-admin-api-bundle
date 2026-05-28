# React 19 + react-admin 5.10 + MUI 6 compatibility

## Status

`@freema/react-admin-api-bundle` is React-19-ready as of `1.1.x`. The published `peerDependencies` accept both React 18.3+ and React 19, so consumers can upgrade on their own schedule without forcing the rest of the ecosystem to follow in lock-step.

| Peer | Accepted range | Notes |
|---|---|---|
| `react` | `^18.3.0 \|\| ^19.0.0` | 18.3 is the lowest version with the new concurrent / Suspense semantics RA 5.10 relies on. |
| `react-dom` | `^18.3.0 \|\| ^19.0.0` | Must match `react`. |
| `react-admin` | `^5.0.0` | Recommended ≥ 5.10 (Suspense in `<Resource queryOptions>`, headless components). |
| `ra-core` | `^5.0.0` | Same. |

The bundle's TypeScript code is intentionally framework-light — no JSX, no lifecycle hooks — so it works under both renderers without conditional logic.

## Recommended dependency matrix

| Layer | Suggested version | Why |
|---|---|---|
| `react` / `react-dom` | `19.0.x` | Concurrent rendering + use() + Suspense everywhere; React Compiler RC. |
| `react-admin` | `5.10.x` or newer | Built-in Suspense, headless mode (`<ListBase>`, `useShowController`), TanStack Query 5. |
| `@tanstack/react-query` | `^5.51.0` | Shipped via RA; needed for `queryClient.cancelQueries({ queryKey })` helpers. |
| `@mui/material` | `^6.0.0` | RA 5.10 fully supports MUI 6. |
| `@mui/icons-material` | `^6.0.0` | Match `@mui/material`. |
| `@emotion/react` + `@emotion/styled` | `^11.13.0` | MUI 6 styling engine. |
| `react-router-dom` | `^6.x` (default) / `^7.x` (opt-in) | RA 5.10 still ships with RR6. RR7 works in projects that opt in; see "react-router 7" below. |
| `@symfony/webpack-encore` (Symfony app) | `^5.3.0` | TypeScript 5.6 friendly. |
| `vite` (standalone SPA) | `^5.4.0` | Stable with React 19 + plugin-react ^4.3. |

## Gotchas

### React 19 strict effects

React 19 retains the "double-invoke in dev" strict effect behaviour. The bundle data provider is pure (no module-level mutable state), so consumers should not see duplicate requests in dev unless `<StrictMode>` is paired with synchronous side-effects in their own queries — keep `useEffect` bodies idempotent or move side-effects to event handlers.

### Suspense fallback flicker

`<Resource queryOptions={{ suspense: true }} />` requires a Suspense boundary around the `<Admin>` itself, otherwise RA falls back to its internal loading state which often flickers. Use:

```tsx
<Suspense fallback={<Loading />}>
    <Admin dataProvider={dataProvider} authProvider={authProvider}>
        <Resource name="users" queryOptions={{ suspense: true }} />
    </Admin>
</Suspense>
```

### React Compiler audit

The React Compiler (RC at the time of writing) is opt-in. Run `npx react-compiler-healthcheck` against the bundle's `assets/src` — every module exports either a pure function or a const object, so it passes without modifications. If you adopt the compiler in your host app, leave the bundle as-is.

### react-router 7

RA 5.10 still ships `react-router-dom` 6 internally. You can run RA inside an RR7 host without breakage (the two routers don't share state), but you cannot pass RR7 navigation helpers into RA components directly. Document this in your own dependency upgrade plan.

### MUI 6 theme tokens

MUI 6 introduced colour scheme tokens (`palette.background.default`, `palette.text.primary`) that supersede the older `palette.type` API. RA 5.10 reads both, but consumers building custom themes should mirror the bundled defaults — see `theme.ts` in your host app.

## What the bundle does NOT do

- The bundle does not pin React. Choose your renderer; the bundle will follow.
- The bundle does not opt into React Compiler — that's a host-level decision.
- The bundle does not migrate you to RR7. If you adopt RR7 in your host, do it on your own schedule; RA 5.10 keeps using RR6 internally.

## Upgrade checklist for consumers

1. `npm pkg set 'dependencies.react=^19.0.0' 'dependencies.react-dom=^19.0.0'`.
2. Bump `react-admin` to `5.10.x`, `@mui/material` + `@mui/icons-material` to `^6.0.0`.
3. Wrap `<Admin>` in a `<Suspense fallback={...}>` if you want to use `queryOptions: { suspense: true }`.
4. Run `npm install` and the host's test suite.
5. Optional: run `npx react-compiler-healthcheck` for an opt-in compiler audit.
