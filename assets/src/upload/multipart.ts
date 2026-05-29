/**
 * Helpers for turning react-admin `<FileInput>` / `<ImageInput>` payloads into the
 * multipart/form-data body the bundle's ResourceController expects when a Content-Type
 * of `multipart/form-data` is received.
 *
 * The detection is intentionally shallow (one level): RA file inputs deposit either a
 * `File` instance directly on `data.<field>` or an object like `{ rawFile: File, ...meta }`
 * — both shapes are handled. Anything else falls back to JSON encoding.
 */

const RA_FILE_KEYS = ['rawFile', 'src'] as const;

const isPlainFile = (value: unknown): value is File =>
    typeof File !== 'undefined' && value instanceof File;

const extractRaRawFile = (value: unknown): File | undefined => {
    if (!value || typeof value !== 'object') {
        return undefined;
    }
    const obj = value as Record<string, unknown>;
    for (const key of RA_FILE_KEYS) {
        const v = obj[key];
        if (isPlainFile(v)) {
            return v;
        }
    }
    return undefined;
};

export const containsFiles = (data: Record<string, unknown> | null | undefined): boolean => {
    if (!data) {
        return false;
    }
    return Object.values(data).some((v) => isPlainFile(v) || extractRaRawFile(v) !== undefined);
};

/**
 * Serialise a react-admin record into FormData. Scalar fields are stringified, file fields
 * are appended as-is, and nested objects (other than file wrappers) are JSON-encoded so the
 * server can still receive them as a single field.
 */
export const toFormData = (data: Record<string, unknown>): FormData => {
    const fd = new FormData();
    for (const [key, value] of Object.entries(data)) {
        if (isPlainFile(value)) {
            fd.append(key, value);
            continue;
        }
        const rawFile = extractRaRawFile(value);
        if (rawFile) {
            fd.append(key, rawFile);
            const obj = value as Record<string, unknown>;
            for (const metaKey of Object.keys(obj)) {
                if (RA_FILE_KEYS.includes(metaKey as (typeof RA_FILE_KEYS)[number])) {
                    continue;
                }
                fd.append(`${key}__${metaKey}`, String(obj[metaKey] ?? ''));
            }
            continue;
        }
        if (value === null || value === undefined) {
            fd.append(key, '');
            continue;
        }
        if (typeof value === 'object') {
            fd.append(key, JSON.stringify(value));
            continue;
        }
        fd.append(key, String(value));
    }
    return fd;
};
