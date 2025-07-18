<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Request;

/**
 * Unified request object for list operations.
 * This is the standardized output from all providers.
 */
readonly class ListDataRequest
{
    public function __construct(
        private ?int $limit = null,
        private ?int $offset = null,
        private ?string $sortField = null,
        private ?string $sortOrder = null,
        private ?string $filter = null,
        private array $filterValues = [],
    ) {
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function getOffset(): ?int
    {
        return $this->offset;
    }

    public function getSortField(): ?string
    {
        return $this->sortField;
    }

    public function getSortOrder(): ?string
    {
        return $this->sortOrder;
    }

    public function getFilter(): ?string
    {
        return $this->filter;
    }

    /**
     * @return array<string, mixed>
     */
    public function getFilterValues(): array
    {
        return $this->filterValues;
    }
}
