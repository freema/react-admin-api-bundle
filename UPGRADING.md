# Upgrading

## From 1.2 to 1.3

### Symfony 8

The bundle supports Symfony 8. Symfony 8 needs PHP 8.4 and DoctrineBundle 3,
so an application moving to it follows DoctrineBundle's own upgrade notes.
Nothing changes for applications that stay on Symfony 6.4 or 7.

### `SoftDeleteTrait::delete()` works

`SoftDeleteTrait::delete()` passed the entity id where `DeleteDataResult`
expects a status, so every delete failed with a `TypeError` after the entity
had already been soft-deleted (or removed). It now answers like `DeleteTrait`:
success, or `400 {"error": "Entity with ID … not found"}` for an unknown id.

### Sorting on doctrine/orm 3.7+

doctrine/orm 3.7 deprecates passing the sort direction to
`QueryBuilder::orderBy()` as a string. `ListTrait` and `ListRelatedToTrait`
now pass the `SortDirection` enum where the installed ORM accepts it, and keep
the string on older versions. Nothing to do.

## From 1.1 to 1.2

1.2.0 is a security release. It does not remove any public API, but the
following behaviour changed. Check each item against your application.

### `ResourceAccessEvent` now runs for every endpoint

`react_admin_api.resource_access` used to be dispatched only for `list`. It is
now dispatched first in every endpoint, with these operations:

| Endpoint | `getOperation()` | `getResourceId()` | `getContext()` |
|---|---|---|---|
| `GET /{resource}` | `list` | `null` | `[]` |
| `GET /{resource}/{id}` | `get` | `{id}` | `[]` |
| `POST /{resource}` | `create` | `null` | `[]` |
| `PUT /{resource}/{id}` | `update` | `{id}` | `[]` |
| `DELETE /{resource}/{id}` | `delete` | `{id}` | `[]` |
| `DELETE /{resource}` | `deleteMany` | `null` | `['ids' => [...]]` |
| `GET /{resource}/{id}/{related}` | `get` on `{resource}`, then `list` on `{related}` | `{id}`, then `null` | `[]`, then `['parentResource' => ..., 'parentId' => ...]` |

A cancelled event answers `403 {"error": "Access denied"}`.

What to do:

- A listener written for `list` only, which cancels without looking at
  `getOperation()`, now also blocks detail, create, update and delete. If that
  is not what you want, check the operation.
- If you relied on the listener to protect writes, nothing to do: it now does.
- `isWriteOperation()` now also returns `true` for `deleteMany`.

### Filter and sort fields are restricted

`ListTrait` and `ListRelatedToTrait` used to put the client's filter keys and
`sort_field` into DQL unchecked. Now they accept:

- a key of `getCustomFilters()`, a sort-map key or an association filter, as
  before (these are defined by your code);
- otherwise only a mapped field of the entity that the resource exposes: its
  identifier and the public properties of its DTO.

Anything else, and a sort order other than `ASC`/`DESC` (case-insensitive,
empty means `ASC`), is answered with `400 {"error": "..."}`
(`InvalidListRequestException`).

What to do:

- If the frontend filters or sorts on a column that is not in the DTO, either
  add it to the DTO, handle it in `getCustomFilters()`, or list the allowed
  fields explicitly:

  ```php
  protected function getFilterableFields(): ?array
  {
      return ['id', 'name', 'email', 'createdAt'];
  }

  protected function getSortableFields(): ?array
  {
      return ['id', 'name', 'createdAt'];
  }
  ```

- A repository that overrides `applyFilters()` and calls the trait for the
  remaining keys should pass only those keys, see
  [doc/filter-operators.md](doc/filter-operators.md).
- Calling the repository directly (not through the bundle's controllers) still
  works: without a DTO class every mapped field is allowed.

### Page size is capped

A list page holds at most `ListDataRequest::MAX_LIMIT` (1000) records, the size
react-admin's export asks for. Bigger `per_page` / `range` values are reduced;
a negative page or offset is treated as the first page. A related-resource list
requested without pagination returns the first 1000 records instead of all.

### Related-resource lists work again

`ListRelatedToTrait` called `getPage()` / `getPerPage()`, which do not exist on
`ListDataRequest`, so every related list failed with 500. It
now uses `getOffset()` / `getLimit()`.

### `DtoFactory` assigns public properties only

The factory used reflection to write any property named in the request body,
including private, protected and static ones. It now skips them. Keep values a
client must not set in non-public properties, or out of the DTO.

### Request body must be a JSON object

`POST` and `PUT` with a body that is not a JSON object or array (for example
`"text"` or `42`) are answered with `400 {"error": "Invalid JSON provided"}`
instead of 500.
