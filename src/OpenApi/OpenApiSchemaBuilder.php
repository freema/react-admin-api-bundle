<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\OpenApi;

use Freema\ReactAdminApiBundle\OpenApi\Attribute\ApiProperty;
use Freema\ReactAdminApiBundle\Service\ResourceConfigurationService;

/**
 * Build an OpenAPI 3.1 spec by reflecting over the configured resources + their DTO classes.
 *
 * Conventions for each resource path "{r}" backed by DTO class D:
 *   GET    /api/{r}           list (paginated; query: page, per_page, sort_field, sort_order, filter)
 *   POST   /api/{r}           create
 *   GET    /api/{r}/{id}      get one
 *   PUT    /api/{r}/{id}      update
 *   DELETE /api/{r}/{id}      delete
 *
 * Schemas component is filled with one entry per DTO class, keyed by its short class name.
 */
final class OpenApiSchemaBuilder
{
    public function __construct(
        private readonly ResourceConfigurationService $resourceConfiguration,
        private readonly string $title = 'React Admin API',
        private readonly string $version = '1.0.0',
        private readonly string $apiPrefix = '/api',
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $paths = [];
        $schemas = [];

        foreach ($this->resourceConfiguration->getConfiguredResources() as $resource) {
            $dtoClass = $this->resourceConfiguration->getResourceDtoClass($resource);
            $schemaName = $this->schemaName($dtoClass);
            $schemas[$schemaName] = $this->buildSchemaForDto($dtoClass);

            $basePath = sprintf('%s/%s', rtrim($this->apiPrefix, '/'), $resource);
            $itemPath = $basePath . '/{id}';
            $ref = ['$ref' => '#/components/schemas/' . $schemaName];

            $paths[$basePath] = $this->mergePathItem($paths[$basePath] ?? [], [
                'get' => $this->buildListOperation($resource, $ref),
                'post' => $this->buildCreateOperation($resource, $ref),
            ]);
            $paths[$itemPath] = $this->mergePathItem($paths[$itemPath] ?? [], [
                'get' => $this->buildGetOneOperation($resource, $ref),
                'put' => $this->buildUpdateOperation($resource, $ref),
                'delete' => $this->buildDeleteOperation($resource),
            ]);
        }

        return [
            'openapi' => '3.1.0',
            'info' => [
                'title' => $this->title,
                'version' => $this->version,
                'description' => 'Automatically generated from freema/react-admin-api-bundle resource configuration.',
            ],
            'paths' => $paths,
            'components' => ['schemas' => $schemas],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildSchemaForDto(string $dtoClass): array
    {
        $reflection = new \ReflectionClass($dtoClass);
        $properties = [];
        $required = [];

        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $name = $property->getName();
            $apiPropAttr = $property->getAttributes(ApiProperty::class)[0] ?? null;
            $apiProp = $apiPropAttr?->newInstance();

            $schema = $this->reflectPropertyType($property);
            if ($apiProp?->description !== null) {
                $schema['description'] = $apiProp->description;
            }
            if ($apiProp?->example !== null) {
                $schema['example'] = $apiProp->example;
            }
            if ($apiProp?->format !== null) {
                $schema['format'] = $apiProp->format;
            }
            if ($apiProp?->readOnly === true) {
                $schema['readOnly'] = true;
            }
            if ($apiProp?->writeOnly === true) {
                $schema['writeOnly'] = true;
            }

            $properties[$name] = $schema;
            if ($name !== 'id' && !$this->isPropertyNullable($property)) {
                $required[] = $name;
            }
        }

        $schema = [
            'type' => 'object',
            'properties' => $properties,
        ];
        if ($required !== []) {
            $schema['required'] = $required;
        }

        return $schema;
    }

    /**
     * @return array<string, mixed>
     */
    private function reflectPropertyType(\ReflectionProperty $property): array
    {
        $type = $property->getType();
        if ($type === null) {
            return ['type' => 'string', 'nullable' => true];
        }

        if ($type instanceof \ReflectionUnionType || $type instanceof \ReflectionIntersectionType) {
            return ['oneOf' => array_map(
                fn (\ReflectionType $t) => $this->mapTypeName($t instanceof \ReflectionNamedType ? $t->getName() : 'string'),
                $type->getTypes(),
            )];
        }

        if ($type instanceof \ReflectionNamedType) {
            $schema = $this->mapTypeName($type->getName());
            if ($type->allowsNull()) {
                $schema['nullable'] = true;
            }
            return $schema;
        }

        return ['type' => 'string'];
    }

    private function isPropertyNullable(\ReflectionProperty $property): bool
    {
        $type = $property->getType();
        if ($type === null) {
            return true;
        }
        return $type->allowsNull();
    }

    /**
     * @return array<string, mixed>
     */
    private function mapTypeName(string $name): array
    {
        return match ($name) {
            'int', 'integer' => ['type' => 'integer'],
            'float', 'double' => ['type' => 'number', 'format' => 'float'],
            'bool', 'boolean' => ['type' => 'boolean'],
            'string' => ['type' => 'string'],
            'array' => ['type' => 'array', 'items' => ['type' => 'object', 'additionalProperties' => true]],
            'object', 'stdClass' => ['type' => 'object', 'additionalProperties' => true],
            'mixed' => ['type' => 'object', 'additionalProperties' => true],
            \DateTime::class, \DateTimeImmutable::class, \DateTimeInterface::class => [
                'type' => 'string',
                'format' => 'date-time',
            ],
            default => ['type' => 'object', 'additionalProperties' => true],
        };
    }

    private function schemaName(string $dtoClass): string
    {
        $parts = explode('\\', $dtoClass);
        return (string) end($parts);
    }

    /**
     * @param array<string, mixed> $existing
     * @param array<string, mixed> $additions
     *
     * @return array<string, mixed>
     */
    private function mergePathItem(array $existing, array $additions): array
    {
        foreach ($additions as $key => $value) {
            $existing[$key] = $value;
        }
        return $existing;
    }

    /**
     * @param array<string, mixed> $ref
     *
     * @return array<string, mixed>
     */
    private function buildListOperation(string $resource, array $ref): array
    {
        return [
            'tags' => [$resource],
            'summary' => sprintf('List %s', $resource),
            'parameters' => [
                ['name' => 'page', 'in' => 'query', 'schema' => ['type' => 'integer', 'default' => 1]],
                ['name' => 'per_page', 'in' => 'query', 'schema' => ['type' => 'integer', 'default' => 10]],
                ['name' => 'sort_field', 'in' => 'query', 'schema' => ['type' => 'string']],
                ['name' => 'sort_order', 'in' => 'query', 'schema' => ['type' => 'string', 'enum' => ['ASC', 'DESC']]],
                ['name' => 'filter', 'in' => 'query', 'schema' => ['type' => 'string'], 'description' => 'JSON-encoded filter object'],
            ],
            'responses' => [
                '200' => [
                    'description' => 'OK',
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'data' => ['type' => 'array', 'items' => $ref],
                                    'total' => ['type' => 'integer'],
                                ],
                                'required' => ['data', 'total'],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $ref
     *
     * @return array<string, mixed>
     */
    private function buildCreateOperation(string $resource, array $ref): array
    {
        return [
            'tags' => [$resource],
            'summary' => sprintf('Create %s', $resource),
            'requestBody' => [
                'required' => true,
                'content' => ['application/json' => ['schema' => $ref]],
            ],
            'responses' => [
                '201' => [
                    'description' => 'Created',
                    'content' => ['application/json' => ['schema' => $ref]],
                ],
                '400' => ['description' => 'Validation error'],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $ref
     *
     * @return array<string, mixed>
     */
    private function buildGetOneOperation(string $resource, array $ref): array
    {
        return [
            'tags' => [$resource],
            'summary' => sprintf('Get %s by id', $resource),
            'parameters' => [['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'string']]],
            'responses' => [
                '200' => ['description' => 'OK', 'content' => ['application/json' => ['schema' => $ref]]],
                '404' => ['description' => 'Not found'],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $ref
     *
     * @return array<string, mixed>
     */
    private function buildUpdateOperation(string $resource, array $ref): array
    {
        return [
            'tags' => [$resource],
            'summary' => sprintf('Update %s', $resource),
            'parameters' => [['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'string']]],
            'requestBody' => [
                'required' => true,
                'content' => ['application/json' => ['schema' => $ref]],
            ],
            'responses' => [
                '200' => ['description' => 'OK', 'content' => ['application/json' => ['schema' => $ref]]],
                '400' => ['description' => 'Validation error'],
                '404' => ['description' => 'Not found'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildDeleteOperation(string $resource): array
    {
        return [
            'tags' => [$resource],
            'summary' => sprintf('Delete %s', $resource),
            'parameters' => [['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'string']]],
            'responses' => [
                '204' => ['description' => 'Deleted'],
                '404' => ['description' => 'Not found'],
            ],
        ];
    }
}
