import { test } from 'node:test';
import { strict as assert } from 'node:assert';
import { createCookieAuthProvider } from '../src/auth/cookieAuthProvider';

const makeFetch = (
    impl: (input: string, init?: RequestInit) => Promise<Response>,
): typeof fetch => impl as unknown as typeof fetch;

test('checkAuth succeeds when /api/me responds with 200', async () => {
    const calls: Array<{ url: string; init?: RequestInit }> = [];
    const provider = createCookieAuthProvider({
        fetch: makeFetch(async (url, init) => {
            calls.push({ url, init });
            return new Response(JSON.stringify({ success: true, data: { id: 'me-1', fullName: 'Me' } }), {
                status: 200,
                headers: { 'Content-Type': 'application/json' },
            });
        }),
    });
    await provider.checkAuth?.({} as any);
    assert.equal(calls.length, 1);
    assert.equal(calls[0].url, '/api/me');
    assert.equal((calls[0].init?.credentials ?? 'default'), 'include');
});

test('checkAuth rejects when /api/me responds with 401', async () => {
    const provider = createCookieAuthProvider({
        fetch: makeFetch(async () => new Response('', { status: 401 })),
    });
    await assert.rejects(provider.checkAuth?.({} as any), (err: { status?: number }) => err.status === 401);
});

test('getIdentity unwraps the {success,data} envelope by default', async () => {
    const provider = createCookieAuthProvider({
        fetch: makeFetch(async () =>
            new Response(
                JSON.stringify({
                    success: true,
                    data: { id: 42, fullName: 'Alice', email: 'a@example.com', roles: ['ROLE_ADMIN'] },
                }),
                { status: 200 },
            ),
        ),
    });
    const id = await provider.getIdentity?.();
    assert.equal(id?.id, 42);
    assert.equal(id?.fullName, 'Alice');
});

test('getPermissions returns roles from /api/me', async () => {
    const provider = createCookieAuthProvider({
        fetch: makeFetch(async () =>
            new Response(JSON.stringify({ data: { id: 1, roles: ['ROLE_ADMIN', 'ROLE_USER'] } }), {
                status: 200,
            }),
        ),
    });
    const perms = await provider.getPermissions?.({} as any);
    assert.deepEqual(perms, ['ROLE_ADMIN', 'ROLE_USER']);
});

test('checkError fires onUnauthorized on 401/403', async () => {
    let unauthorizedFired = 0;
    const provider = createCookieAuthProvider({
        onUnauthorized: () => {
            unauthorizedFired += 1;
        },
        fetch: makeFetch(async () => new Response('', { status: 200 })),
    });
    await assert.rejects(provider.checkError?.({ status: 401 } as any));
    assert.equal(unauthorizedFired, 1);

    // checkError must NOT redirect on non-auth errors (e.g. 500).
    await provider.checkError?.({ status: 500 } as any);
    assert.equal(unauthorizedFired, 1);
});
