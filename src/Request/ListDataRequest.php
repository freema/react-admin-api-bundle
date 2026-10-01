<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Request;

/**
 * Unified request object for list operations.
 * This is the standardized output from all providers.
 *
 * The page size is capped at MAX_LIMIT and a negative offset becomes 0, so a
 * client cannot ask for an unbounded or invalid page.
 */
readonly class ListDataRequest
{
    /** Largest page a client can request (react-admin's export uses 1000). */
    public const MAX_LIMIT = 1000;

    private ?int $limit;
    private ?int $offset;

    /**
     * @param array<string, mixed> $filterValues
     * @param class-string|null    $dtoClass     DTO of the listed resource; set by the
     *                                           bundle's controllers so repositories can
     *                                           restrict filters and sorting to exposed fields
     */
    public function __construct(
        ?int $limit = null,
        ?int $offset = null,
        private ?string $sortField = null,
        private ?string $sortOrder = null,
        private ?string $filter = null,
        private array $filterValues = [],
        private ?string $dtoClass = null,
    ) {
        $this->limit = $limit === null ? null : max(1, min($limit, self::MAX_LIMIT));
        $this->offset = $offset === null ? null : max(0, $offset);
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

    /**
     * @return class-string|null
     */
    public function getDtoClass(): ?string
    {
        return $this->dtoClass;
    }

    /**
     * @param class-string $dtoClass
     */
    public function withDtoClass(string $dtoClass): self
    {
        return new self($this->limit, $this->offset, $this->sortField, $this->sortOrder, $this->filter, $this->filterValues, $dtoClass);
    }

    /**
     * A copy with other filter values, e.g. after a repository handled some
     * keys itself and passes the rest on to ListTrait::applyFilters().
     *
     * @param array<string, mixed> $filterValues
     */
    public function withFilterValues(array $filterValues): self
    {
        $filter = $filterValues === [] ? null : (json_encode($filterValues) ?: null);

        return new self($this->limit, $this->offset, $this->sortField, $this->sortOrder, $filter, $filterValues, $this->dtoClass);
    }
}
