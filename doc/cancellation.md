# Request cancellation, timeouts and TanStack Query keys

`@freema/react-admin-api-bundle` 1.1+ propagates **`AbortSignal`** through every data-provider method and ships first-class TanStack Query key helpers.

## Why it matters

React-admin 5 builds on **TanStack Query 5**, which gives every `queryFn` an `AbortSignal` that fires when the React tree unmounts the consumer, when navigation supersedes the current screen, or when the user types in a search box that supersedes an older list query. Without forwarding that signal into `fetch`, stale requests continue to run after the user already moved on. With React 19 concurrent rendering + Suspense the cost is amplified — a single user gesture can trigger several speculative renders, each one issuing a request the next render would otherwise discard.

## Forwarding the react-admin signal

The bundled data provider does the wiring for you — every CRUD method (`getList`, `getOne`, `getMany`, `getManyReference`, `create`, `update`, `updateMany`, `delete`, `deleteMany`) passes `params.signal` into `fetch` after combining it with any default signal you supplied on construction.

```ts
import { createDataProvider } from '@freema/react-admin-api-bundle';

const dataProvider = createDataProvider('/api/admin');
// react-admin will pass its own AbortSignal in params.signal; nothing else to do.
```

## Adding a per-request timeout

```ts
const dataProvider = createDataProvider('/api/admin', {
    timeoutMs: 30_000,
});
```

When the configured timeout elapses without a response the request is aborted with an `AbortError` / `TimeoutError`. The timeout is composed *together* with the react-admin signal — whichever fires first wins.

## Adding default fetch options (cookies, headers, ...)

```ts
const dataProvider = createDataProvider('/api/admin', {
    fetchOptions: { credentials: 'include' },
});
```

`signal`, `method`, `body` and `headers` set by the provider always override `fetchOptions` (the type system enforces this).

## Cancelling queries manually

Use `getResourceQueryKeys()` to obtain stable TanStack Query key tuples that mirror what react-admin uses internally:

```ts
import { useQueryClient } from '@tanstack/react-query';
import { getResourceQueryKeys } from '@freema/react-admin-api-bundle';

const usersKeys = getResourceQueryKeys('users');

// Cancel every in-flight list query for `users` (e.g. when leaving the screen).
queryClient.cancelQueries({ queryKey: usersKeys.lists });

// Invalidate one specific row after a mutation.
queryClient.invalidateQueries({ queryKey: usersKeys.detail(42) });

// Invalidate every query under the resource (rarely needed).
queryClient.invalidateQueries({ queryKey: usersKeys.all });
```

The available keys are:

| Helper | Tuple |
|---|---|
| `keys.all` | `['<resource>']` |
| `keys.lists` | `['<resource>', 'getList']` |
| `keys.list(params)` | `['<resource>', 'getList', params]` |
| `keys.details` | `['<resource>', 'getOne']` |
| `keys.detail(id)` | `['<resource>', 'getOne', { id }]` |
| `keys.many` | `['<resource>', 'getMany']` |
| `keys.manyReferences` | `['<resource>', 'getManyReference']` |
| `keys.manyReference(target, id)` | `['<resource>', 'getManyReference', { target, id }]` |

## Combining signals yourself

If you need to compose signals outside of the data provider (e.g. inside a custom React hook), use the same helper the provider uses:

```ts
import { combineSignals, timeoutSignal } from '@freema/react-admin-api-bundle';

const userSignal = controller.signal;
const combined = combineSignals(userSignal, timeoutSignal(5_000));
await fetch(url, { signal: combined });
```

Both helpers prefer the native `AbortSignal.any()` / `AbortSignal.timeout()` when available and fall back to a hand-rolled implementation in older runtimes.
