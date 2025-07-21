import { fetchUtils } from 'ra-core';
/**
 * Custom data provider for React Admin API Bundle
 * This is the default provider that matches the bundle's API format
 */
export const customDataProvider = (apiUrl) => {
    const httpClient = (url, options = {}) => {
        if (!options.headers) {
            options.headers = new Headers({ Accept: 'application/json' });
        }
        return fetchUtils.fetchJson(url, options);
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
            const responses = await Promise.all(params.ids.map((id) => httpClient(`${apiUrl}/${resource}/${id}`, {
                method: 'PUT',
                body: JSON.stringify(params.data),
            })));
            return { data: responses.map(({ json }) => json.id) };
        },
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
};
