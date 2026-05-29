<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Concurrency;

use Freema\ReactAdminApiBundle\Interface\AdminEntityInterface;

/**
 * Produces a weak ETag (`W/"..."`) for an admin entity based on its id and any of
 * `getUpdatedAt()` / `getVersion()` accessors. Returns a stable string so two requests
 * against the same entity revision produce the same value.
 *
 * Used by ResourceController to set the `ETag` header on read responses and to
 * verify `If-Match` on write requests for optimistic concurrency control.
 */
final class EtagGenerator
{
    /**
     * @param array<string, mixed> $extra extra fingerprint inputs (e.g. a payload hash)
     */
    public function generate(AdminEntityInterface $entity, array $extra = []): string
    {
        $parts = [
            'class' => $entity::class,
            'id' => $this->extractIdentifier($entity),
            'version' => $this->extractVersion($entity),
            'updatedAt' => $this->extractUpdatedAtTimestamp($entity),
        ];
        if ($extra !== []) {
            $parts['extra'] = $extra;
        }
        $hash = md5((string) json_encode($parts, JSON_THROW_ON_ERROR));

        return sprintf('W/"%s"', $hash);
    }

    public function matches(string $ifMatchHeader, string $currentEtag): bool
    {
        if ($ifMatchHeader === '' || $ifMatchHeader === '*') {
            return true;
        }
        $candidates = array_map('trim', explode(',', $ifMatchHeader));

        return in_array($currentEtag, $candidates, true);
    }

    private function extractIdentifier(AdminEntityInterface $entity): mixed
    {
        if (method_exists($entity, 'getId')) {
            return $entity->getId();
        }
        if (property_exists($entity, 'id')) {
            return $entity->id ?? null;
        }

        return null;
    }

    private function extractVersion(AdminEntityInterface $entity): mixed
    {
        if (method_exists($entity, 'getVersion')) {
            return $entity->getVersion();
        }
        if (property_exists($entity, 'version')) {
            return $entity->version ?? null;
        }

        return null;
    }

    private function extractUpdatedAtTimestamp(AdminEntityInterface $entity): ?int
    {
        if (method_exists($entity, 'getUpdatedAt')) {
            $value = $entity->getUpdatedAt();
            if ($value instanceof \DateTimeInterface) {
                return $value->getTimestamp();
            }
        }

        return null;
    }
}
