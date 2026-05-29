<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\OpenApi\Attribute;

/**
 * Optional DTO property metadata picked up by the OpenAPI schema builder + TypeScript codegen.
 *
 * Place on public properties of a DTO that extends AdminApiDto:
 *
 *   #[ApiProperty(description: 'Slug used in public URLs', example: 'my-blog-post', format: 'slug')]
 *   public string $slug = '';
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class ApiProperty
{
    public function __construct(
        public readonly ?string $description = null,
        public readonly mixed $example = null,
        public readonly ?string $format = null,
        public readonly bool $readOnly = false,
        public readonly bool $writeOnly = false,
    ) {
    }
}
