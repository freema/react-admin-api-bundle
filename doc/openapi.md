# OpenAPI spec generation

The bundle ships a console command that derives a full OpenAPI 3.1 spec from your `react_admin_api.resources` configuration plus reflection of each DTO class.

## Generate

```bash
# Stdout (default: yaml)
php bin/console react-admin-api:dump-openapi

# To a file (yaml or json)
php bin/console react-admin-api:dump-openapi --output=public/openapi.yaml
php bin/console react-admin-api:dump-openapi --format=json --output=public/openapi.json
```

The spec covers, for every configured resource path `r` backed by DTO class `D`:

| Method | Path | Operation |
|---|---|---|
| `GET` | `/api/{r}` | List with `page`, `per_page`, `sort_field`, `sort_order`, `filter` |
| `POST` | `/api/{r}` | Create |
| `GET` | `/api/{r}/{id}` | Get one |
| `PUT` | `/api/{r}/{id}` | Update |
| `DELETE` | `/api/{r}/{id}` | Delete |

DTO classes become components/schemas entries keyed by their short class name. Each public property is reflected; nullable types are marked accordingly, `\DateTimeImmutable` becomes `string` with `format: date-time`.

## Enriching schemas with `#[ApiProperty]`

Attach `Freema\ReactAdminApiBundle\OpenApi\Attribute\ApiProperty` to public DTO properties to add description, example, format, or read/write-only flags:

```php
use Freema\ReactAdminApiBundle\OpenApi\Attribute\ApiProperty;

final class UserDto extends AdminApiDto
{
    public ?int $id = null;

    #[ApiProperty(description: 'Login email', example: 'alice@example.com', format: 'email')]
    public string $email = '';

    #[ApiProperty(description: 'Server-managed timestamp', readOnly: true)]
    public \DateTimeImmutable $createdAt;
    /* … */
}
```

## Hosting Swagger UI

The bundle does not bundle a Swagger UI. Either:

1. Drop the spec where your existing API doc setup expects it (e.g. `public/openapi.yaml` consumed by NelmioApiDocBundle), or
2. Serve `swagger-ui` static assets and point at `/openapi.yaml`.

## Limitations

- Only public properties on the DTO are walked. Computed values must be exposed as additional public properties.
- Composite/union types are emitted as `oneOf`; very complex unions (e.g. `array|object`) fall back to `object` with `additionalProperties: true`.
- The current builder does not introspect related resources (T-future); they are documented as plain references in the path's response schema.
