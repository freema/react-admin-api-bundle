/**
 * Minimal Server-Sent Events helper for streaming resource events from the bundle's
 * future `/api/_stream/{resource}` endpoint (or any compatible Mercure topic).
 *
 * Each parsed event is delivered to the callback as `{ type, payload }`; the caller
 * is responsible for invoking `queryClient.invalidateQueries(...)` etc.
 *
 * Returns an `unsubscribe` function that closes the underlying EventSource.
 */
export interface ResourceEvent {
    type: 'created' | 'updated' | 'deleted' | 'ping' | string;
    payload?: unknown;
    id?: string | number;
}

export interface SubscribeOptions {
    /** Override the EventSource URL. Default: `${apiUrl}/_stream/${resource}`. */
    url?: string;
    /**
     * Pass-through to `new EventSource(url, { withCredentials })`. Defaults to true
     * so the existing session cookie is sent along.
     */
    withCredentials?: boolean;
    /** Callback invoked on connection errors (network drop, server down, etc.). */
    onError?: (err: Event) => void;
    /**
     * Optional EventSource constructor override -- useful for tests or when running
     * inside a worker that needs the `eventsource` npm polyfill.
     */
    eventSourceImpl?: typeof EventSource;
}

export const subscribeResource = (
    apiUrl: string,
    resource: string,
    onEvent: (event: ResourceEvent) => void,
    opts: SubscribeOptions = {},
): (() => void) => {
    const url = opts.url ?? `${apiUrl.replace(/\/$/, '')}/_stream/${resource}`;
    const Impl = opts.eventSourceImpl ?? (typeof EventSource !== 'undefined' ? EventSource : undefined);
    if (!Impl) {
        throw new Error('subscribeResource: no global EventSource available -- supply opts.eventSourceImpl');
    }
    const source = new Impl(url, { withCredentials: opts.withCredentials ?? true });

    source.onmessage = (msg: MessageEvent): void => {
        try {
            const data = JSON.parse((msg.data ?? '').toString());
            onEvent(data as ResourceEvent);
        } catch {
            // Some servers emit ping comments / non-JSON heartbeats; ignore.
        }
    };
    if (opts.onError) {
        source.onerror = opts.onError;
    }

    return () => {
        source.close();
    };
};
