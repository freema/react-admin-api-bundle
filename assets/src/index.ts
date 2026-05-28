/**
 * React Admin API Bundle — Frontend Assets
 *
 * This package provides a TypeScript data provider for React Admin applications that
 * integrate with the Freema\ReactAdminApiBundle Symfony bundle.
 */

export {
    createDataProvider,
    customDataProvider,
} from './data-provider';

export { createCookieAuthProvider } from './auth/cookieAuthProvider';
export type { CookieAuthOptions } from './auth/cookieAuthProvider';

export { containsFiles, toFormData } from './upload/multipart';

export type {
    ReactAdminApiOptions,
    DataProviderFactory,
    ListResponse,
    GetOneResponse,
    CreateResponse,
    UpdateResponse,
    DeleteResponse,
} from './types';

export { default } from './data-provider';
