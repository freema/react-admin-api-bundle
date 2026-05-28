import type { AuthProvider, UserIdentity } from 'react-admin';

export interface CookieAuthOptions {
    /** GET endpoint that returns the current user, e.g. `/api/me`. Defaults to `/api/me`. */
    meUrl?: string;
    /** Page to redirect to when an unauthenticated user is detected. Defaults to `/login`. */
    loginUrl?: string;
    /** POST endpoint that clears the session cookie. Defaults to `/logout`. */
    logoutUrl?: string;
    /**
     * Adapter that turns the `meUrl` JSON payload into a react-admin UserIdentity.
     * The default unwraps `{success, data}` envelopes (the shape produced by
     * Freema\ReactAdminApiBundle exception listener); replace it if you ship a
     * different envelope.
     */
    parseIdentity?: (raw: unknown) => UserIdentity;
    /** Hook called when the cookie is missing / 401 / 403. Defaults to `location.href = loginUrl`. */
    onUnauthorized?: () => void;
    /** Optional override for the HTTP layer. Defaults to global `fetch`. */
    fetch?: typeof fetch;
}

const defaultParseIdentity = (raw: unknown): UserIdentity => {
    if (!raw || typeof raw !== 'object') {
        throw new Error('Invalid identity response');
    }
    const obj = raw as Record<string, unknown>;
    const data = (obj.data ?? obj) as Record<string, unknown>;
    const idValue = data.id ?? data.reglogId ?? data.identityId ?? data.username;
    if (idValue === undefined || idValue === null) {
        throw new Error('Identity payload is missing an id field');
    }
    return {
        id: idValue as string | number,
        fullName: (data.fullName as string) ?? (data.email as string) ?? String(idValue),
        avatar: data.avatar as string | undefined,
        ...data,
    };
};

/**
 * Build a react-admin AuthProvider that relies on an HttpOnly session cookie set by the
 * Symfony backend (e.g. via the Freema\ReactAdminApiBundle / a custom OAuth flow).
 *
 * The provider never touches `localStorage` — the cookie is fully managed by the browser.
 * `checkAuth`, `getIdentity` and `getPermissions` all share the same `meUrl` round-trip so
 * a single 401 is enough to redirect the user back to the login screen.
 *
 *   const authProvider = createCookieAuthProvider({
 *       meUrl: '/api/me',
 *       logoutUrl: '/admin/auth/logout',
 *       loginUrl: '/admin/login',
 *   });
 *   <Admin authProvider={authProvider} ... />
 */
export const createCookieAuthProvider = (opts: CookieAuthOptions = {}): AuthProvider => {
    const meUrl = opts.meUrl ?? '/api/me';
    const loginUrl = opts.loginUrl ?? '/login';
    const logoutUrl = opts.logoutUrl ?? '/logout';
    const parseIdentity = opts.parseIdentity ?? defaultParseIdentity;
    const onUnauthorized =
        opts.onUnauthorized ??
        (() => {
            if (typeof window !== 'undefined') {
                window.location.href = loginUrl;
            }
        });
    const httpFetch = opts.fetch ?? (typeof fetch !== 'undefined' ? fetch : undefined);
    if (!httpFetch) {
        throw new Error('createCookieAuthProvider: no global fetch available — supply opts.fetch');
    }

    const callMe = async (): Promise<unknown> => {
        const response = await httpFetch(meUrl, { credentials: 'include' });
        if (!response.ok) {
            throw Object.assign(new Error('Unauthorized'), { status: response.status });
        }
        return response.json();
    };

    return {
        login: async () => {
            /* server-driven login: the host app redirects to its own login endpoint */
        },
        logout: async () => {
            try {
                await httpFetch(logoutUrl, { method: 'POST', credentials: 'include' });
            } catch {
                // ignore — cookie may already be cleared
            }
            onUnauthorized();
        },
        checkAuth: async () => {
            await callMe();
        },
        checkError: async (error: unknown) => {
            const status = (error as { status?: number } | null | undefined)?.status;
            if (status === 401 || status === 403) {
                onUnauthorized();
                throw error;
            }
        },
        getIdentity: async (): Promise<UserIdentity> => {
            const raw = await callMe();
            return parseIdentity(raw);
        },
        getPermissions: async () => {
            try {
                const raw = await callMe();
                const data = (raw as { data?: { roles?: string[] } }).data;
                return data?.roles ?? [];
            } catch {
                return [];
            }
        },
    };
};
