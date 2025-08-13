<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Request\Provider\List;

use Freema\ReactAdminApiBundle\Request\ListDataRequest;
use Symfony\Component\HttpFoundation\Request;

class RestProvider implements ListDataRequestProviderInterface
{
    public function supports(Request $request): bool
    {
        // REST provider uses 'range' and 'sort' as JSON arrays
        return $request->query->has('range') && $request->query->has('sort');
    }

    public function getPriority(): int
    {
        return 100;
    }

    public function getName(): string
    {
        return 'rest';
    }

    public function createRequest(Request $request): ListDataRequest
    {
        $limit = null;
        $offset = null;
        $sortField = null;
        $sortOrder = null;
        $filter = null;
        $filterValues = [];

        // Parse range parameter [start, end]
        if ($request->query->has('range')) {
            $rangeParam = $request->query->get('range');
            if (is_string($rangeParam)) {
                $range = json_decode($rangeParam, true);
                if (is_array($range) && count($range) === 2) {
                    $rangeStart = (int) $range[0];
                    $rangeEnd = (int) $range[1];
                    $offset = $rangeStart;
                    $limit = $rangeEnd - $rangeStart + 1;
                }
            }
        }

        // Parse sort parameter ["field", "order"]
        if ($request->query->has('sort')) {
            $sortParam = $request->query->get('sort');
            if (is_string($sortParam)) {
                $sort = json_decode($sortParam, true);
                if (is_array($sort) && count($sort) === 2) {
                    $sortField = $sort[0];
                    $sortOrder = strtoupper((string) $sort[1]);
                }
            }
        }

        // Parse filter parameter
        $filterParam = $request->query->get('filter', null);
        if ($filterParam && is_string($filterParam)) {
            $decoded = json_decode($filterParam, true);
            $filterValues = is_array($decoded) ? $decoded : [];
            $filter = $filterParam;
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
