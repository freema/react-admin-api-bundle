import type { DataProvider } from 'react-admin';
import { fetchUtils } from 'ra-core';

const httpClient = (url: string, options: any = {}) => {
    if (!options.headers) {
        options.headers = new Headers({ Accept: 'application/json' });
    }

    // For dev purposes, no auth token needed
    // const token = getCookie(COOKIE_NAMES.ACCESS_TOKEN);
    // if (!token) {
    //     throw new Error('Missing auth token');
    // }
    // options.headers.set('x-authorization', `Bearer ${token}`);
    
    return fetchUtils.fetchJson(url, options);
};

const apiUrl = 'http://127.0.0.1:8080/api';

export const customDataProvider: DataProvider = {
    getList: async (resource, params) => {
        // @ts-expect-error
        const { page, perPage } = params.pagination;
        // @ts-expect-error
        const { field, order } = params.sort;
        const query = {
            sort_field: field,
            sort_order: order,
            page,
            per_page: perPage,
            filter: JSON.stringify(params.filter),
        };
        const url = `${apiUrl}/${resource}?${fetchUtils.queryParameters(query)}`;
        const { json, headers } = await httpClient(url);
        return {
            data: json.data,
            total: parseInt(headers.get('x-content-range') || json.total || '0', 10),
        };
    },

    getOne: async (resource, params) => {
        const { json } = await httpClient(`${apiUrl}/${resource}/${params.id}`);
        return { data: json };
    },

    getMany: async (resource, params) => {
        const query = {
            filter: JSON.stringify({ id: params.ids }),
        };
        const url = `${apiUrl}/${resource}?${fetchUtils.queryParameters(query)}`;
        const { json } = await httpClient(url);
        return { data: json.data };
    },

    getManyReference: async (resource, params) => {
        const { page, perPage } = params.pagination;
        const { field, order } = params.sort;
        const query = {
            sort_field: field,
            sort_order: order,
            page,
            per_page: perPage,
            filter: JSON.stringify({
                ...params.filter,
                [params.target]: params.id,
            }),
        };
        const url = `${apiUrl}/${resource}?${fetchUtils.queryParameters(query)}`;
        const { json, headers } = await httpClient(url);
        return {
            data: json.data,
            total: parseInt(headers.get('x-content-range') || json.total || '0', 10),
        };
    },

    update: async (resource, params) => {
        const { json } = await httpClient(`${apiUrl}/${resource}/${params.id}`, {
            method: 'PUT',
            body: JSON.stringify(params.data),
        });
        return { data: json };
    },

    updateMany: async (resource, params) => {
        const responses = await Promise.all(
            params.ids.map((id) =>
                httpClient(`${apiUrl}/${resource}/${id}`, {
                    method: 'PUT',
                    body: JSON.stringify(params.data),
                })
            )
        );
        return { data: responses.map(({ json }) => json.id) };
    },

    // @ts-expect-error
    create: async (resource, params) => {
        const { json } = await httpClient(`${apiUrl}/${resource}`, {
            method: 'POST',
            body: JSON.stringify(params.data),
        });
        return { data: { ...params.data, id: json.id } };
    },

    delete: async (resource, params) => {
        const { json } = await httpClient(`${apiUrl}/${resource}/${params.id}`, {
            method: 'DELETE',
        });
        return { data: json };
    },

    deleteMany: async (resource, params) => {
        const { json } = await httpClient(`${apiUrl}/${resource}`, {
            method: 'DELETE',
            body: JSON.stringify({ filter: { id: params.ids } }),
        });
        return { data: json };
    },
};