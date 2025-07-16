<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Result;

use Freema\ReactAdminApiBundle\Dto\AdminApiDto;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Result of a list operation with pagination.
 */
class ListDataResult
{
    /**
     * @param array<AdminApiDto> $data The list of DTOs to return
     * @param int $total The total number of items available
     */
    public function __construct(private array $data, private int $total)
    {
    }

    /**
     * @return array<AdminApiDto>
     */
    public function getData(): array
    {
        return $this->data;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    /**
     * Create a JSON response with the result data formatted for React Admin.
     */
    public function createResponse(): JsonResponse
    {
        $responseData = [];
        foreach ($this->data as $dto) {
            $responseData[] = $dto->toArray();
        }

        $response = new JsonResponse([
            'data' => $responseData,
            'total' => $this->getTotal(),
        ]);
        $response->headers->set('Content-Range', sprintf('items 0-%d/%d', count($this->data) - 1, $this->total));
        $response->headers->set('Access-Control-Expose-Headers', 'Content-Range');

        return $response;
    }
}