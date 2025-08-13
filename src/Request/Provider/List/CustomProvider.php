<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Request\Provider\List;

use Freema\ReactAdminApiBundle\Request\ListDataRequest;
use Symfony\Component\HttpFoundation\Request;

class CustomProvider implements ListDataRequestProviderInterface
{
    public function supports(Request $request): bool
    {
        // Custom provider uses 'page', 'per_page', 'sort_field', 'sort_order'
        return $request->query->has('page')
            || $request->query->has('per_page')
            || $request->query->has('sort_field')
            || $request->query->has('sort_order');
    }

    public function getPriority(): int
    {
        return 50;
    }

    public function getName(): string
    {
        return 'custom';
    }

    public function createRequest(Request $request): ListDataRequest
    {
        $page = $request->query->has('page') ? (int) $request->query->get('page') : null;
        $perPage = $request->query->has('per_page') ? (int) $request->query->get('per_page') : null;

        $limit = $perPage;
        $offset = null;
        if ($page !== null && $perPage !== null) {
            $offset = ($page - 1) * $perPage;
        }

        $sortField = $request->query->get('sort_field', null);
        $sortOrder = $request->query->get('sort_order', null);
        if ($sortOrder) {
            $sortOrder = strtoupper((string) $sortOrder);
        }

        // Parse filter parameter
        $filter = $request->query->get('filter', null);
        $filterValues = [];
        if ($filter && is_string($filter)) {
            $decoded = json_decode($filter, true);
            $filterValues = is_array($decoded) ? $decoded : [];
        }

        // Ensure proper types for constructor
        $filterJson = null;
        if (!empty($filterValues)) {
            $filterJson = json_encode($filterValues);
            if ($filterJson === false) {
                $filterJson = null;
            }
        }
        
        return new ListDataRequest(
            limit: $limit,
            offset: $offset,
            sortField: is_string($sortField) ? $sortField : null,
            sortOrder: is_string($sortOrder) ? $sortOrder : null,
            filter: $filterJson,
            filterValues: $filterValues
        );
    }
}
