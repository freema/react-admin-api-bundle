/**
 * React Admin API Bundle - Frontend Assets
 * 
 * This package provides data provider for React Admin applications
 * that integrate with the React Admin API Bundle for Symfony.
 */

export {
    createDataProvider,
    customDataProvider,
} from './data-provider';

export type {
    ReactAdminApiOptions,
    DataProviderFactory,
    ListResponse,
    GetOneResponse,
    CreateResponse,
    UpdateResponse,
    DeleteResponse,
} from './types';

// Default export
export { default } from './data-provider';