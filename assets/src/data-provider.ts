import { customDataProvider } from './customDataProvider';
import type { DataProviderFactory } from './types';

/**
 * Create custom data provider for React Admin API Bundle
 */
export const createDataProvider: DataProviderFactory = (apiUrl: string) => {
    return customDataProvider(apiUrl);
};

// Export individual providers
export { customDataProvider };

// Export types
export type { DataProviderFactory };

// Default export
export default createDataProvider;