import { customDataProvider } from './customDataProvider';
import type { DataProviderFactory, ReactAdminApiOptions } from './types';

/**
 * Create custom data provider for React Admin API Bundle.
 *
 * @example
 *   const dataProvider = createDataProvider('/api/admin');
 *
 * @example With cookie auth + 30s timeout:
 *   const dataProvider = createDataProvider('/api/admin', {
 *     timeoutMs: 30_000,
 *     fetchOptions: { credentials: 'include' },
 *   });
 */
export const createDataProvider: DataProviderFactory = (
    apiUrl: string,
    opts?: ReactAdminApiOptions,
) => {
    return customDataProvider(apiUrl, opts);
};

export { customDataProvider };

export type { DataProviderFactory, ReactAdminApiOptions };

export default createDataProvider;
