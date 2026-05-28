<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Tests\OpenApi;

use Freema\ReactAdminApiBundle\Dto\AdminApiDto;
use Freema\ReactAdminApiBundle\Interface\AdminEntityInterface;
use Freema\ReactAdminApiBundle\OpenApi\Attribute\ApiProperty;
use Freema\ReactAdminApiBundle\OpenApi\OpenApiSchemaBuilder;
use Freema\ReactAdminApiBundle\Service\ResourceConfigurationService;
use PHPUnit\Framework\TestCase;

final class SchemaBuilderFixtureEntity implements AdminEntityInterface
{
}

final class SchemaBuilderFixtureDto extends AdminApiDto
{
    public ?int $id = null;
    public string $name = '';
    public ?string $description = null;
    public bool $active = true;
    public int $sortOrder = 0;
    public \DateTimeImmutable $createdAt;
    /** @var array<string, mixed> */
    public array $payload = [];

    #[ApiProperty(description: 'Slug used in public URLs', example: 'my-fixture', format: 'slug')]
    public string $slug = '';

    public static function getMappedEntityClass(): string
    {
        return SchemaBuilderFixtureEntity::class;
    }

    public static function createFromEntity(AdminEntityInterface $entity): self
    {
        return new self();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'active' => $this->active,
            'sortOrder' => $this->sortOrder,
            'createdAt' => $this->createdAt->format(\DateTimeInterface::ATOM),
            'payload' => $this->payload,
            'slug' => $this->slug,
        ];
    }
}

final class OpenApiSchemaBuilderTest extends TestCase
{
    private OpenApiSchemaBuilder $builder;

    protected function setUp(): void
    {
        $config = new ResourceConfigurationService([
            'fixtures' => ['dto_class' => SchemaBuilderFixtureDto::class],
        ]);
        $this->builder = new OpenApiSchemaBuilder($config, 'Fixture API', '1.0.0', '/api');
    }

    public function testBuildEmitsPathsForEveryResource(): void
    {
        $spec = $this->builder->build();
        self::assertSame('3.1.0', $spec['openapi']);
        self::assertArrayHasKey('/api/fixtures', $spec['paths']);
        self::assertArrayHasKey('/api/fixtures/{id}', $spec['paths']);
        self::assertArrayHasKey('get', $spec['paths']['/api/fixtures']);
        self::assertArrayHasKey('post', $spec['paths']['/api/fixtures']);
        self::assertArrayHasKey('get', $spec['paths']['/api/fixtures/{id}']);
        self::assertArrayHasKey('put', $spec['paths']['/api/fixtures/{id}']);
        self::assertArrayHasKey('delete', $spec['paths']['/api/fixtures/{id}']);
    }

    public function testBuildEmitsComponentSchemaWithCorrectShape(): void
    {
        $spec = $this->builder->build();
        $schema = $spec['components']['schemas']['SchemaBuilderFixtureDto'];

        self::assertSame('object', $schema['type']);
        self::assertSame(['type' => 'integer', 'nullable' => true], $schema['properties']['id']);
        self::assertSame(['type' => 'string'], $schema['properties']['name']);
        self::assertSame(['type' => 'string', 'nullable' => true], $schema['properties']['description']);
        self::assertSame(['type' => 'boolean'], $schema['properties']['active']);
        self::assertSame(['type' => 'integer'], $schema['properties']['sortOrder']);
        self::assertSame(['type' => 'string', 'format' => 'date-time'], $schema['properties']['createdAt']);

        self::assertSame(
            ['type' => 'string', 'description' => 'Slug used in public URLs', 'example' => 'my-fixture', 'format' => 'slug'],
            $schema['properties']['slug'],
        );

        self::assertContains('name', $schema['required']);
        self::assertNotContains('id', $schema['required']);
        self::assertNotContains('description', $schema['required']);
    }

    public function testListOperationDocumentsPaginationParameters(): void
    {
        $spec = $this->builder->build();
        $params = array_column($spec['paths']['/api/fixtures']['get']['parameters'], 'name');
        self::assertSame(['page', 'per_page', 'sort_field', 'sort_order', 'filter'], $params);
    }
}
