# File uploads via multipart/form-data

The bundle's ResourceController defaults to `application/json`, which doesn't work for react-admin `<FileInput>` / `<ImageInput>`. The new `MultipartDtoFactory` plugs into the controller path for requests whose `Content-Type` starts with `multipart/form-data`.

## Frontend

`@freema/react-admin-api-bundle` exposes two helpers in `upload/multipart`:

- `containsFiles(data)` — detects either a plain `File` instance or react-admin's `{ rawFile: File, ... }` wrapper.
- `toFormData(data)` — serialises a record into `FormData`, keeping file fields as binaries, JSON-encoding nested objects, stringifying scalars, and folding `<field>__<meta>` keys into the file's neighbouring metadata.

Use them to override `create` / `update` on a per-resource basis:

```ts
import { containsFiles, toFormData } from '@freema/react-admin-api-bundle';
import { createDataProvider } from '@freema/react-admin-api-bundle';

const base = createDataProvider('/api/admin', { fetchOptions: { credentials: 'include' } });

export const dataProvider = {
    ...base,
    create: async (resource, params) => {
        if (containsFiles(params.data)) {
            const res = await fetch(`/api/admin/${resource}`, {
                method: 'POST',
                credentials: 'include',
                body: toFormData(params.data),
            });
            return { data: { ...params.data, id: (await res.json()).id } };
        }
        return base.create(resource, params);
    },
};
```

A first-class `multipart: 'auto'` option on the data provider is on the roadmap (T15) — for now consumers compose explicitly so they keep control over which resources go multipart.

## Backend

`MultipartDtoFactory::createFromRequest($request, FooDto::class)` returns a DTO populated from `$request->request->all()` + `$request->files->all()`. The bundled `DtoFactory` does the property assignment, so any setter / public-property mapping you already have keeps working.

The factory expects file form-field names to match DTO property names. Metadata fields emitted with the convention `<field>__<sub>` (mirroring `toFormData`) are folded into a nested array under `<field>` before the DTO is hydrated.

The controller switches based on `MultipartDtoFactory::isMultipart($request)`:

```php
if (MultipartDtoFactory::isMultipart($request)) {
    $dto = $multipartFactory->createFromRequest($request, $resourceDtoClass);
} else {
    $dto = $this->dtoFactory->createFromArray(
        json_decode($request->getContent(), true),
        $resourceDtoClass,
    );
}
```

DTO properties for file fields should use `Symfony\Component\HttpFoundation\File\UploadedFile|null`. Persist the uploaded file from the entity's setter / a repository hook; the bundle does **not** manage filesystem storage — that's intentional, the use cases vary too much.

## Tests

Cover both happy paths in your own integration test (the bundle test suite covers the helpers but not the filesystem write):

```php
$client->request(
    'POST',
    '/api/admin/avatars',
    ['name' => 'profile-pic'],
    ['file' => new UploadedFile($tmpPath, 'pic.png', 'image/png')],
);
$this->assertResponseStatusCodeSame(201);
```
