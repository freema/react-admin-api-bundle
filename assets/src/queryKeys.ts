/**
 * TanStack Query 5 / react-admin 5 query-key helpers.
 *
 * These mirror the keys react-admin uses internally so consumers can call
 * `queryClient.invalidateQueries(getResourceQueryKeys('users').lists)` after a mutation
 * without guessing the key shape.
 *
 * The values are typed `as const` so consumers get exact tuple types in TypeScript.
 */
export const getResourceQueryKeys = (resource: string) => ({
    /** All queries for the resource (lists + detail + custom). */
    all: [resource] as const,
    /** Every getList query (any params). */
    lists: [resource, 'getList'] as const,
    /** A specific getList query (use the same params you pass to dataProvider.getList). */
    list: (params: unknown) => [resource, 'getList', params] as const,
    /** Every getOne query. */
    details: [resource, 'getOne'] as const,
    /** A specific getOne query (by id). */
    detail: (id: string | number) => [resource, 'getOne', { id }] as const,
    /** Every getMany query. */
    many: [resource, 'getMany'] as const,
    /** Every getManyReference query (any target). */
    manyReferences: [resource, 'getManyReference'] as const,
    /** A specific getManyReference query by target + id. */
    manyReference: (target: string, id: string | number) =>
        [resource, 'getManyReference', { target, id }] as const,
});

export type ResourceQueryKeys = ReturnType<typeof getResourceQueryKeys>;
