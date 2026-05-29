import { test } from 'node:test';
import { strict as assert } from 'node:assert';
import { customDataProvider } from '../src/customDataProvider';

interface CapturedCall {
    url: string;
    init: RequestInit;
}

const makeFetchStub = (
    response: { status?: number; body?: unknown; headers?: Record<string, string> } = {},
) => {
    const calls: CapturedCall[] = [];
    const stub = (url: string, init: RequestInit = {}) => {
        calls.push({ url, init });
        if (init.signal?.aborted) {
            return Promise.reject(
                Object.assign(new Error('Aborted'), { name: 'AbortError' }),
            );
        }
        return new Promise((resolve, reject) => {
            const headers = new Headers(response.headers ?? {});
            const body = JSON.stringify(response.body ?? { data: [], total: 0 });
            const onAbort = (): void => {
                reject(Object.assign(new Error('Aborted'), { name: 'AbortError' }));
            };
            init.signal?.addEventListener('abort', onAbort, { once: true });
            setTimeout(() => {
                init.signal?.removeEventListener('abort', onAbort);
                resolve({
                    status: response.status ?? 200,
                    statusText: 'OK',
                    headers,
                    ok: true,
                    text: () => Promise.resolve(body),
                });
            }, 10);
        }) as unknown as Promise<Response>;
    };
    return { stub, calls };
};

test('getList forwards the AbortSignal supplied by react-admin to fetch', async () => {
    const original = globalThis.fetch;
    const { stub, calls } = makeFetchStub({ body: { data: [], total: 0 } });
    globalThis.fetch = stub as unknown as typeof fetch;
    try {
        const provider = customDataProvider('/api');
        const controller = new AbortController();
        const promise = provider.getList('users', {
            pagination: { page: 1, perPage: 10 },
            sort: { field: 'id', order: 'ASC' },
            filter: {},
            signal: controller.signal,
        } as any);
        controller.abort();
        await assert.rejects(promise, (err: { name?: string }) => err.name === 'AbortError');
        assert.ok(calls.length === 1, 'fetch was called once');
        assert.ok(calls[0].init.signal, 'a signal was forwarded');
    } finally {
        globalThis.fetch = original;
    }
});

test('timeoutMs aborts the request when no response arrives in time', async () => {
    const original = globalThis.fetch;
    const slow = (url: string, init: RequestInit = {}) =>
        new Promise((_resolve, reject) => {
            init.signal?.addEventListener('abort', () => {
                reject(Object.assign(new Error('Aborted'), { name: 'AbortError' }));
            }, { once: true });
        }) as unknown as Promise<Response>;
    globalThis.fetch = slow as unknown as typeof fetch;
    try {
        const provider = customDataProvider('/api', { timeoutMs: 25 });
        const start = Date.now();
        await assert.rejects(
            provider.getOne('users', { id: 1 } as any),
            (err: { name?: string }) => err.name === 'AbortError' || err.name === 'TimeoutError',
        );
        assert.ok(Date.now() - start < 500, 'aborted well within 500ms');
    } finally {
        globalThis.fetch = original;
    }
});

test('fetchOptions defaults (e.g. credentials) are merged into every request', async () => {
    const original = globalThis.fetch;
    const { stub, calls } = makeFetchStub({ body: { id: 1 } });
    globalThis.fetch = stub as unknown as typeof fetch;
    try {
        const provider = customDataProvider('/api', {
            fetchOptions: { credentials: 'include' },
        });
        await provider.getOne('users', { id: 1 } as any);
        assert.equal(calls[0].init.credentials, 'include');
    } finally {
        globalThis.fetch = original;
    }
});
