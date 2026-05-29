import type { DataProvider } from 'react-admin';
import { fetchUtils } from 'ra-core';
import { combineSignals, timeoutSignal } from './abortSignal';
import type { ReactAdminApiOptions } from './types';

type FetchOptions = RequestInit & { signal?: AbortSignal };

/**
 * Custom data provider for React Admin API Bundle.
 *
 * Supports:
 * - AbortSignal propagation from react-admin / TanStack Query 5 (request cancellation).
 * - Optional per-request timeout (`opts.timeoutMs`).
 * - Caller-supplied default fetch options (`opts.fetchOptions`), e.g. `{ credentials: 'include' }`.
 */
export const customDataProvider = (
    apiUrl: string,
    opts: ReactAdminApiOptions = {},
): DataProvider => {
    const { timeoutMs, fetchOptions } = opts;

    const buildSignal = (incoming?: AbortSignal): AbortSignal | undefined => {
        const signals: Array<AbortSignal | undefined> = [];
        if (fetchOptions && 'signal' in fetchOptions) {
            const optsSignal = (fetchOptions as RequestInit).signal;
            if (optsSignal) {
                signals.push(optsSignal);
            }
        }
        if (incoming) {
            signals.push(incoming);
        }
        if (typeof timeoutMs === 'number' && timeoutMs > 0) {
            signals.push(timeoutSignal(timeoutMs));
        }
        return combineSignals(...signals);
    };

    const httpClient = (url: string, options: FetchOptions = {}) => {
        const merged: FetchOptions = { ...(fetchOptions as RequestInit | undefined), ...options };
        if (!merged.headers) {
            merged.headers = new Headers({ Accept: 'application/json' });
        }
        const signal = buildSignal(options.signal);
        if (signal) {
            merged.signal = signal;
        }
        return fetchUtils.fetchJson(url, merged);
    };

    return {
        getList: async (resource, params) => {
            const { page, perPage } = params.pagination || { page: 1, perPage: 10 };
            const { field, order } = params.sort || { field: 'id', order: 'ASC' };
            const query = {
                sort_field: field,
                sort_order: order,
                page,
                per_page: perPage,
                filter: JSON.stringify(params.filter || {}),
            };
            const url = `${apiUrl}/${resource}?${fetchUtils.queryParameters(query)}`;
            const { json, headers } = await httpClient(url, { signal: params.signal });
            return {
                data: json.data,
                total: parseInt(headers.get('x-content-range') || json.total || '0', 10),
            };
        },

        getOne: async (resource, params) => {
            const { json } = await httpClient(`${apiUrl}/${resource}/${params.id}`, {
                signal: params.signal,
            });
            return { data: json };
        },

        getMany: async (resource, params) => {
            const query = {
                filter: JSON.stringify({ id: params.ids }),
            };
            const url = `${apiUrl}/${resource}?${fetchUtils.queryParameters(query)}`;
            const { json } = await httpClient(url, { signal: params.signal });
            return { data: json.data };
        },

        getManyReference: async (resource, params) => {
            const { page, perPage } = params.pagination || { page: 1, perPage: 10 };
            const { field, order } = params.sort || { field: 'id', order: 'ASC' };
            const query = {
                sort_field: field,
                sort_order: order,
                page,
                per_page: perPage,
                filter: JSON.stringify({
                    ...(params.filter || {}),
                    [params.target]: params.id,
                }),
            };
            const url = `${apiUrl}/${resource}?${fetchUtils.queryParameters(query)}`;
            const { json, headers } = await httpClient(url, { signal: params.signal });
            return {
                data: json.data,
                total: parseInt(headers.get('x-content-range') || json.total || '0', 10),
            };
        },

        update: async (resource, params) => {
            const { json } = await httpClient(`${apiUrl}/${resource}/${params.id}`, {
                method: 'PUT',
                body: JSON.stringify(params.data),
                signal: params.signal,
            });
            return { data: json };
        },

        updateMany: async (resource, params) => {
            const responses = await Promise.all(
                params.ids.map((id) =>
                    httpClient(`${apiUrl}/${resource}/${id}`, {
                        method: 'PUT',
                        body: JSON.stringify(params.data),
                        signal: params.signal,
                    }),
                ),
            );
            return { data: responses.map(({ json }) => json.id) };
        },

        create: async (resource, params) => {
            const { json } = await httpClient(`${apiUrl}/${resource}`, {
                method: 'POST',
                body: JSON.stringify(params.data),
                signal: params.signal,
            });
            return { data: { ...params.data, id: json.id } as any };
        },

        delete: async (resource, params) => {
            const { json } = await httpClient(`${apiUrl}/${resource}/${params.id}`, {
                method: 'DELETE',
                signal: params.signal,
            });
            return { data: json };
        },

        deleteMany: async (resource, params) => {
            const { json } = await httpClient(`${apiUrl}/${resource}`, {
                method: 'DELETE',
                body: JSON.stringify({ filter: { id: params.ids } }),
                signal: params.signal,
            });
            return { data: json };
        },
    };
};
