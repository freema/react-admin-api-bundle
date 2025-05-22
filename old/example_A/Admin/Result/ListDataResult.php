<?php

declare(strict_types=1);

namespace Vlp\Mailer\Api\Admin\Result;

use Symfony\Component\HttpFoundation\JsonResponse;

class ListDataResult
{
    public function __construct(private readonly array $data, private readonly int $totalCount)
    {
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getTotalCount(): int
    {
        return $this->totalCount;
    }

    public function createResponse(): JsonResponse
    {
        $responseData['data'] = $this->data;

        $response = new JsonResponse($responseData);
        $response->headers->set('x-content-range', (string) $this->totalCount);

        return $response;
    }
}
