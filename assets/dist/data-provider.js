import { customDataProvider } from './customDataProvider';
/**
 * Create custom data provider for React Admin API Bundle
 */
export const createDataProvider = (apiUrl) => {
    return customDataProvider(apiUrl);
};
// Export individual providers
export { customDataProvider };
// Default export
export default createDataProvider;
