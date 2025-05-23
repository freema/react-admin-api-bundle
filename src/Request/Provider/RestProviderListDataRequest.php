<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Request\Provider;

use Symfony\Component\HttpFoundation\Request;

/**
 * Handles list requests from React Admin REST provider
 * URL pattern: /api/users?filter=%7B%7D&range=%5B0%2C9%5D&sort=%5B%22id%22%2C%22ASC%22%5D
 */
class RestProviderListDataRequest
{
    private ?int $rangeStart = null;
    private ?int $rangeEnd = null;
    private ?string $sortField = null;
    private ?string $sortOrder = null;
    private ?string $filter = null;
    private array $filterValues = [];

    public function __construct(Request $request)
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

        // Parse filter parameter as JSON object
        $this->filter = $request->query->get('filter', null);
        if ($this->filter) {
            $this->filterValues = json_decode($this->filter, true) ?? [];
        }
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
        if ($this->rangeStart !== null && $this->rangeEnd !== null) {
            return $this->rangeEnd - $this->rangeStart + 1;
        }
        return null;
    }

    public function getOffset(): ?int
    {
        return $this->rangeStart;
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