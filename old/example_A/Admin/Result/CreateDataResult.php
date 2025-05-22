<?php

declare(strict_types=1);

namespace Vlp\Mailer\Api\Admin\Result;

use Symfony\Component\HttpFoundation\JsonResponse;

class CreateDataResult
{
    public function __construct(private readonly bool $status, private readonly array $errorMessage = [])
    {
    }

    public function isStatus(): bool
    {
        return $this->status;
    }

    public function getErrorMessage(): array
    {
        return $this->errorMessage;
    }

    public function createResponse(): JsonResponse
    {
        return new JsonResponse([]);
    }
}
