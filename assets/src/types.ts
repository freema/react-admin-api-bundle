import type { DataProvider } from 'react-admin';

export interface ReactAdminApiOptions {
    /**
     * Per-request timeout in milliseconds. If a request exceeds this duration the underlying
     * fetch is aborted (combined with any AbortSignal supplied by react-admin / TanStack Query).
     *
     * Default: undefined (no timeout — rely on the caller's own AbortSignal).
     */
    timeoutMs?: number;

    /**
     * Additional fetch options merged into every request (e.g. `{ credentials: 'include' }`).
     *
     * Note: `signal`, `method`, `body`, and `headers` are managed by the data provider itself
     * and will override anything supplied here.
     */
    fetchOptions?: Omit<RequestInit, 'signal' | 'method' | 'body' | 'headers'>;
}

export interface ListResponse<T = any> {
    data: T[];
    total: number;
}

export interface GetOneResponse<T = any> {
    data: T;
}

export interface CreateResponse<T = any> {
    data: T;
}

export interface UpdateResponse<T = any> {
    data: T;
}

export interface DeleteResponse<T = any> {
    data: T;
}

export type DataProviderFactory = (apiUrl: string, opts?: ReactAdminApiOptions) => DataProvider;
