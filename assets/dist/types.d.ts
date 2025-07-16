import type { DataProvider } from 'react-admin';
export interface ReactAdminApiOptions {
    apiUrl: string;
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
export type DataProviderFactory = (apiUrl: string) => DataProvider;
