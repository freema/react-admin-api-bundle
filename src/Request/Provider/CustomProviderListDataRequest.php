<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Request\Provider;

use Symfony\Component\HttpFoundation\Request;

/**
 * Handles list requests from custom React Admin data provider
 * URL pattern: /api/users?filter=%7B%7D&page=1&per_page=10&sort_field=id&sort_order=ASC
 */
class CustomProviderListDataRequest
{
    private ?int $page = null;
    private ?int $perPage = null;
    private ?string $sortField = null;
    private ?string $sortOrder = null;
    private ?string $filter = null;
    private array $filterValues = [];

    public function __construct(Request $request)
    {
        // Parse page parameter
        if ($request->query->has('page')) {
            $this->page = (int) $request->query->get('page');
        }

        // Parse per_page parameter
        if ($request->query->has('per_page')) {
            $this->perPage = (int) $request->query->get('per_page');
        }

        // Parse sort_field parameter
        $this->sortField = $request->query->get('sort_field', null);

        // Parse sort_order parameter
        $sortOrder = $request->query->get('sort_order', null);
        if ($sortOrder) {
            $this->sortOrder = strtoupper($sortOrder);
        }

        // Parse filter parameter as JSON object
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

    public function getLimit(): ?int
    {
        return $this->perPage;
    }

    public function getOffset(): ?int
    {
        if ($this->page !== null && $this->perPage !== null) {
            return ($this->page - 1) * $this->perPage;
        }
        return null;
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