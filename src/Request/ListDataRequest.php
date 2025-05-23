<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Request;

/**
 * Unified request object for list operations.
 * This is the standardized output from all providers.
 */
class ListDataRequest
{
    public function __construct(
        private readonly ?int $limit = null,
        private readonly ?int $offset = null,
        private readonly ?string $sortField = null,
        private readonly ?string $sortOrder = null,
        private readonly ?string $filter = null,
        private readonly array $filterValues = []
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