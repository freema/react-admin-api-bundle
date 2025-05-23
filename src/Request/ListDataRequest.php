<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Request;

use Symfony\Component\HttpFoundation\Request;

/**
 * Unified request handler for different React Admin data providers.
 * Automatically detects and handles both REST and Custom provider patterns.
 */
class ListDataRequest
{
    private ?int $page = null;
    private ?int $perPage = null;
    private ?int $rangeStart = null;
    private ?int $rangeEnd = null;
    private ?string $sortField = null;
    private ?string $sortOrder = null;
    private ?string $filter = null;
    private array $filterValues = [];
    private string $providerType;

    public function __construct(Request $request)
    {
        $this->detectProviderType($request);
        $this->parseParameters($request);
    }

    private function detectProviderType(Request $request): void
    {
        // REST provider uses 'range' and 'sort' as JSON arrays
        // Custom provider uses 'page', 'per_page', 'sort_field', 'sort_order'
        
        if ($request->query->has('range') && $request->query->has('sort')) {
            $this->providerType = 'rest';
        } elseif ($request->query->has('page') || $request->query->has('per_page') || $request->query->has('sort_field')) {
            $this->providerType = 'custom';
        } else {
            // Default to custom provider if unclear
            $this->providerType = 'custom';
        }
    }

    private function parseParameters(Request $request): void
    {
        if ($this->providerType === 'rest') {
            $this->parseRestProviderParameters($request);
        } else {
            $this->parseCustomProviderParameters($request);
        }

        // Parse filter parameter (common for both providers)
        $this->filter = $request->query->get('filter', null);
        if ($this->filter) {
            $this->filterValues = json_decode($this->filter, true) ?? [];
        }
    }

    private function parseRestProviderParameters(Request $request): void
    {
        // Parse range parameter [start, end]
        if ($request->query->has('range')) {
            $range = json_decode($request->query->get('range'), true);
            if (is_array($range) && count($range) === 2) {
                $this->rangeStart = (int) $range[0];
                $this->rangeEnd = (int) $range[1];
            }
        }

        // Parse sort parameter ["field", "order"]
        if ($request->query->has('sort')) {
            $sort = json_decode($request->query->get('sort'), true);
            if (is_array($sort) && count($sort) === 2) {
                $this->sortField = $sort[0];
                $this->sortOrder = strtoupper($sort[1]);
            }
        }
    }

    private function parseCustomProviderParameters(Request $request): void
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
    }

    public function getProviderType(): string
    {
        return $this->providerType;
    }

    public function getPage(): ?int
    {
        return $this->page;
    }

    public function getPerPage(): ?int
    {
        return $this->perPage;
    }

    public function getRangeStart(): ?int
    {
        return $this->rangeStart;
    }

    public function getRangeEnd(): ?int
    {
        return $this->rangeEnd;
    }

    public function getLimit(): ?int
    {
        if ($this->providerType === 'rest') {
            if ($this->rangeStart !== null && $this->rangeEnd !== null) {
                return $this->rangeEnd - $this->rangeStart + 1;
            }
        } else {
            return $this->perPage;
        }
        return null;
    }

    public function getOffset(): ?int
    {
        if ($this->providerType === 'rest') {
            return $this->rangeStart;
        } else {
            if ($this->page !== null && $this->perPage !== null) {
                return ($this->page - 1) * $this->perPage;
            }
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