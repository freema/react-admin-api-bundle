<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Request;

use Freema\ReactAdminApiBundle\Interface\DtoInterface;
use Freema\ReactAdminApiBundle\Service\DtoFactory;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

/**
 * Builds a DTO from a multipart/form-data request. POST/PUT bodies that contain at least
 * one file part are routed here instead of through the JSON decoder. Each `UploadedFile`
 * is mapped onto the DTO under its form-field name; scalar fields are passed through.
 *
 * Field-naming convention for nested file metadata: keys named `<field>__<sub>` are
 * folded into a nested array under `<field>`. This matches the FormData emitted by
 * `assets/src/upload/multipart.ts`.
 */
final class MultipartDtoFactory
{
    public function __construct(private readonly DtoFactory $dtoFactory)
    {
    }

    /**
     * @param class-string<DtoInterface> $dtoClass
     */
    public function createFromRequest(Request $request, string $dtoClass): DtoInterface
    {
        $data = $request->request->all();
        foreach ($data as $key => $value) {
            if (!is_string($key) || !str_contains($key, '__')) {
                continue;
            }
            [$parent, $sub] = explode('__', $key, 2);
            $existing = $data[$parent] ?? [];
            if (!is_array($existing)) {
                $existing = [];
            }
            $existing[$sub] = $value;
            $data[$parent] = $existing;
            unset($data[$key]);
        }

        foreach ($request->files->all() as $field => $file) {
            $data[$field] = $this->normalizeFile($file);
        }

        return $this->dtoFactory->createFromArray($data, $dtoClass);
    }

    public static function isMultipart(Request $request): bool
    {
        $contentType = (string) $request->headers->get('Content-Type', '');

        return str_starts_with($contentType, 'multipart/form-data');
    }

    private function normalizeFile(mixed $file): mixed
    {
        if ($file instanceof UploadedFile) {
            return $file;
        }
        if (is_array($file)) {
            return array_map(fn ($item) => $this->normalizeFile($item), $file);
        }

        return $file;
    }
}
