<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Request;

use Symfony\Component\HttpFoundation\Request;

/**
 * Represents a request to list entities with pagination, sorting and filtering.
 */
class ListDataRequest
{
    private ?int $page = null;
    private ?int $perPage = null;
    private ?string $sortField = null;
    private ?string $sortOrder = null;
    private ?string $filter = null;
    private array $filterValues = [];

    public function __construct(Request $request)
    {
        if ($request->query->has('page')) {
            $this->page = (int) $request->query->get('page');
        }
        
        if ($request->query->has('perPage')) {
            $this->perPage = (int) $request->query->get('perPage');
        }
        
        $this->sortField = $request->query->get('sort', null);
        $this->sortOrder = $request->query->get('order', null);
        $this->filter = $request->query->get('filter', null);
        
        if ($this->filter) {
            $this->filterValues = json_decode($this->filter, true) ?? [];
        }
    }

    public function getPage(): ?int
    {
        return $this->page;
    }

    public function getPerPage(): ?int
    {
        return $this->perPage;
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