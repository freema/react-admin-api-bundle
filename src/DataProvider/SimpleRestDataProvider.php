<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\DataProvider;

use Freema\ReactAdminApiBundle\Request\ListDataRequest;
use Symfony\Component\HttpFoundation\Request;

/**
 * Simple REST data provider for ra-data-simple-rest compatibility
 *
 * Expected format:
 * GET /posts?sort=["title","ASC"]&range=[0, 24]&filter={"title":"bar"}
 */
class SimpleRestDataProvider extends AbstractDataProvider
{
    public function supports(Request $request): bool
    {
        // Check if request contains simple-rest specific parameters
        return $request->query->has('sort') && $request->query->has('range');
    }

    public function transformListRequest(Request $request): ListDataRequest
    {
        [$page, $perPage] = $this->extractSimpleRestPagination($request);
        [$sortField, $sortOrder] = $this->extractSimpleRestSort($request);
        $filter = $this->extractSimpleRestFilter($request);

        return $this->createListDataRequest($page, $perPage, $sortField, $sortOrder, $filter);
    }

    public function transformResponse(array $data, int $total): array
    {
        // Simple REST provider expects data directly as array, not wrapped in object
        // The total count is communicated via Content-Range header
        return $data;
    }

    public function getType(): string
    {
        return 'simple_rest';
    }

    /**
     * Extract pagination from simple-rest format: range=[0, 24]
     */
    private function extractSimpleRestPagination(Request $request): array
    {
        $range = $request->query->get('range');
        if (empty($range)) {
            return [1, 10];
        }

        if (is_string($range)) {
            $decoded = json_decode($range, true);
            if (is_array($decoded) && count($decoded) === 2) {
                $start = (int) $decoded[0];
                $end = (int) $decoded[1];
                $perPage = $end - $start + 1;
                $page = (int) floor($start / $perPage) + 1;

                return [$page, $perPage];
            }
        }

        return [1, 10];
    }

    /**
     * Extract sort from simple-rest format: sort=["title","ASC"]
     */
    private function extractSimpleRestSort(Request $request): array
    {
        $sort = $request->query->get('sort');
        if (empty($sort)) {
            return ['id', 'ASC'];
        }

        if (is_string($sort)) {
            $decoded = json_decode($sort, true);
            if (is_array($decoded) && count($decoded) === 2) {
                return [(string) $decoded[0], strtoupper((string) $decoded[1])];
            }
        }

        return ['id', 'ASC'];
    }

    /**
     * Extract filter from simple-rest format: filter={"title":"bar"}
     */
    private function extractSimpleRestFilter(Request $request): array
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
}
