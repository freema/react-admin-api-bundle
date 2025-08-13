<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\DataProvider;

use Freema\ReactAdminApiBundle\Request\ListDataRequest;
use Symfony\Component\HttpFoundation\Request;

/**
 * Custom data provider (default) - matches the original bundle format
 */
class CustomDataProvider extends AbstractDataProvider
{
    public function supports(Request $request): bool
    {
        // Custom provider is the default fallback
        return true;
    }

    public function transformListRequest(Request $request): ListDataRequest
    {
        [$page, $perPage] = $this->extractPagination($request);
        [$sortField, $sortOrder] = $this->extractSort($request);
        $filter = $this->extractFilter($request);

        return $this->createListDataRequest($page, $perPage, $sortField, $sortOrder, $filter);
    }

    public function transformResponse(array $data, int $total): array
    {
        $transformedData = [];
        foreach ($data as $dto) {
            if ($dto instanceof \Freema\ReactAdminApiBundle\Interface\DtoInterface) {
                $transformedData[] = $dto->toArray();
            } else {
                $transformedData[] = $dto; // fallback for non-DTO data
            }
        }

        return [
            'data' => $transformedData,
            'total' => $total,
        ];
    }

    public function getType(): string
    {
        return 'custom';
    }
}
