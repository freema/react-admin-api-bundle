# Real-time updates with Server-Sent Events

`@freema/react-admin-api-bundle` ships `subscribeResource(apiUrl, resource, onEvent, opts?)` -- a thin EventSource wrapper that pairs with any SSE source emitting JSON-encoded resource events. The backend side is intentionally pluggable so consumers can choose between a hand-rolled `StreamedResponse`, Symfony Mercure, or a managed CDN like Pusher.

## Event format

The helper parses each `data:` line as JSON and forwards `{ type, payload?, id? }` to the callback:

```ts
import { subscribeResource } from '@freema/react-admin-api-bundle';
import { useQueryClient } from '@tanstack/react-query';
import { getResourceQueryKeys } from '@freema/react-admin-api-bundle';

const NotificationLive = () => {
    const qc = useQueryClient();
    useEffect(() => subscribeResource('/api', 'notifications', (event) => {
        const keys = getResourceQueryKeys('notifications');
        if (event.type === 'deleted' && event.id) {
            qc.removeQueries({ queryKey: keys.detail(event.id) });
        }
        qc.invalidateQueries({ queryKey: keys.lists });
    }), [qc]);
    return null;
};
```

Anything that doesn't parse as JSON (heartbeat comments, ping lines emitted by upstream proxies) is silently ignored.

## Options

| Option | Default | Notes |
|---|---|---|
| `url` | `${apiUrl}/_stream/${resource}` | Override for Mercure topic URIs (`https://hub/.well-known/mercure?topic=/notifications`). |
| `withCredentials` | `true` | Forwards the session cookie. Required if the SSE endpoint is auth-gated. |
| `onError` | `undefined` | Hook for reconnect telemetry or user-visible toast. |
| `eventSourceImpl` | global `EventSource` | Inject for tests or to use the `eventsource` npm polyfill from a worker. |

The helper returns an unsubscribe function -- call it in your effect's cleanup to close the stream.

## Backend options

The bundle does not ship a pre-baked `/api/_stream/{resource}` controller -- transport choice is project-specific. Three common patterns:

### 1. Symfony Mercure (recommended for production)

```yaml
# config/packages/mercure.yaml
mercure:
    hubs:
        default:
            url: 'https://mercure.internal/.well-known/mercure'
            jwt: { secret: '%env(MERCURE_JWT_SECRET)%' }
```

Publish from your bundle event listener (`react_admin_api.entity_created` / `_updated` / `_deleted`):

```php
$this->hub->publish(new Update('/notifications', json_encode([
    'type' => 'created',
    'id'   => $entity->getId(),
])));
```

Then point the frontend at the Mercure topic:

```ts
subscribeResource('/api', 'notifications', cb, {
    url: 'https://mercure.internal/.well-known/mercure?topic=/notifications',
});
```

### 2. Symfony `StreamedResponse` (dev / small deployments)

For dev or low-traffic admin apps a polling loop inside `StreamedResponse` is enough:

```php
#[Route('/api/_stream/{resource}', methods: ['GET'])]
public function stream(string $resource, EventBuffer $buffer): StreamedResponse
{
    return new StreamedResponse(function () use ($resource, $buffer) {
        while (true) {
            foreach ($buffer->drain($resource) as $event) {
                echo "data: " . json_encode($event) . "\n\n";
            }
            @ob_flush(); flush();
            usleep(500_000);
        }
    }, headers: [
        'Content-Type' => 'text/event-stream',
        'Cache-Control' => 'no-cache, no-transform',
        'X-Accel-Buffering' => 'no',
    ]);
}
```

`EventBuffer` is your own implementation -- typically a Redis list / pub-sub keyed by resource path. Avoid in-memory buffers in production: the worker recycles will drop events.

### 3. Polling fallback

If no SSE infrastructure is available, react-admin's built-in `useGetLive({ pollInterval: 5000 })` still works against the bundle's plain `GET /api/{resource}/{id}` route -- no extra integration needed.

## Reconnect, retry, backoff

`EventSource` already reconnects automatically with the server-provided `retry:` interval. If you need exponential backoff or auth-token refresh, wrap the helper in your own hook and close + reopen on `onError`.
