<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\DataProvider;

use Freema\ReactAdminApiBundle\Request\ListDataRequest;
use Symfony\Component\HttpFoundation\Request;

/**
 * Abstract base class for data providers
 */
abstract class AbstractDataProvider implements DataProviderInterface
{
    /**
     * Extract pagination parameters from request
     */
    protected function extractPagination(Request $request): array
    {
        $page = (int) ($request->query->get('page') ?? 1);
        $perPage = (int) ($request->query->get('per_page') ?? 10);

        return [$page, $perPage];
    }

    /**
     * Extract sort parameters from request
     */
    protected function extractSort(Request $request): array
    {
        $sortField = $request->query->get('sort_field') ?? 'id';
        $sortOrder = strtoupper($request->query->get('sort_order') ?? 'ASC');

        return [$sortField, $sortOrder];
    }

    /**
     * Extract filter parameters from request
     */
    protected function extractFilter(Request $request): array
    {
        $filter = $request->query->get('filter');
        if (empty($filter)) {
            return [];
        }

        if (is_string($filter)) {
            $decoded = json_decode($filter, true);

            return $decoded ?? [];
        }

        return is_array($filter) ? $filter : [];
    }

    /**
     * Create ListDataRequest from extracted parameters
     */
    protected function createListDataRequest(int $page, int $perPage, string $sortField, string $sortOrder, array $filter): ListDataRequest
    {
        // Convert page/perPage to offset/limit
        $offset = ($page - 1) * $perPage;
        $limit = $perPage;

        // Convert filter array to JSON string
        $filterJson = !empty($filter) ? json_encode($filter) : null;

        return new ListDataRequest(
            limit: $limit,
            offset: $offset,
            sortField: $sortField,
            sortOrder: $sortOrder,
            filter: $filterJson,
            filterValues: $filter
        );
    }
}
