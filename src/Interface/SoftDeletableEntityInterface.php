<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Interface;

/**
 * Mark an entity as soft-deletable so the bundle can:
 *   - exclude soft-deleted rows from default list queries,
 *   - opt-in include them via `?include_deleted=1`,
 *   - convert DELETE requests into a `setDeletedAt(now())` flush instead of a physical remove.
 *
 * Implementations typically pair this with a Doctrine `deleted_at` DATETIME column.
 */
interface SoftDeletableEntityInterface extends AdminEntityInterface
{
    public function getDeletedAt(): ?\DateTimeImmutable;

    public function setDeletedAt(?\DateTimeImmutable $deletedAt): void;
}
