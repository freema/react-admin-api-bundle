import { test } from 'node:test';
import { strict as assert } from 'node:assert';
import { subscribeResource, type ResourceEvent } from '../src/realtime/subscribeResource';

class FakeEventSource {
    public readonly url: string;
    public readonly init: { withCredentials?: boolean };
    public onmessage: ((event: { data: string }) => void) | null = null;
    public onerror: ((event: Event) => void) | null = null;
    public closed = false;

    constructor(url: string, init: { withCredentials?: boolean } = {}) {
        this.url = url;
        this.init = init;
    }

    close(): void {
        this.closed = true;
    }
}

const factory = (): {
    Impl: typeof EventSource;
    instances: FakeEventSource[];
} => {
    const instances: FakeEventSource[] = [];
    const Impl = function (url: string, init: { withCredentials?: boolean } = {}) {
        const es = new FakeEventSource(url, init);
        instances.push(es);
        return es;
    } as unknown as typeof EventSource;
    return { Impl, instances };
};

test('subscribeResource builds the default URL and opens a credentialed stream', () => {
    const { Impl, instances } = factory();
    const unsubscribe = subscribeResource('https://api.example.com', 'users', () => {}, {
        eventSourceImpl: Impl,
    });
    assert.equal(instances.length, 1);
    assert.equal(instances[0].url, 'https://api.example.com/_stream/users');
    assert.equal(instances[0].init.withCredentials, true);
    unsubscribe();
    assert.equal(instances[0].closed, true);
});

test('subscribeResource respects an explicit URL override', () => {
    const { Impl, instances } = factory();
    subscribeResource('ignored', 'users', () => {}, {
        eventSourceImpl: Impl,
        url: 'https://hub.example.com/topic/users',
    });
    assert.equal(instances[0].url, 'https://hub.example.com/topic/users');
});

test('subscribeResource parses JSON messages and forwards them to the callback', () => {
    const { Impl, instances } = factory();
    const received: ResourceEvent[] = [];
    subscribeResource('https://api.example.com', 'users', (e) => received.push(e), {
        eventSourceImpl: Impl,
    });
    instances[0].onmessage?.({ data: JSON.stringify({ type: 'created', id: 1 }) });
    instances[0].onmessage?.({ data: JSON.stringify({ type: 'updated', id: 1, payload: { name: 'x' } }) });
    instances[0].onmessage?.({ data: 'this is a heartbeat comment, not JSON' });
    assert.equal(received.length, 2);
    assert.equal(received[0].type, 'created');
    assert.equal(received[1].payload && (received[1].payload as { name: string }).name, 'x');
});
