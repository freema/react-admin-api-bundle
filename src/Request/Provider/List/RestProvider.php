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
            $range = json_decode($request->query->get('range'), true);
            if (is_array($range) && count($range) === 2) {
                $rangeStart = (int) $range[0];
                $rangeEnd = (int) $range[1];
                $offset = $rangeStart;
                $limit = $rangeEnd - $rangeStart + 1;
            }
        }

        // Parse sort parameter ["field", "order"]
        if ($request->query->has('sort')) {
            $sort = json_decode($request->query->get('sort'), true);
            if (is_array($sort) && count($sort) === 2) {
                $sortField = $sort[0];
                $sortOrder = strtoupper($sort[1]);
            }
        }

        // Parse filter parameter
        $filter = $request->query->get('filter', null);
        if ($filter) {
            $filterValues = json_decode($filter, true) ?? [];
        }

        return new ListDataRequest(
            $limit,
            $offset,
            $sortField,
            $sortOrder,
            $filter,
            $filterValues
        );
    }
}
