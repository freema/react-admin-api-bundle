<?php

declare(strict_types=1);

namespace Vlp\Mailer\Api\Admin\Result;

use Symfony\Component\HttpFoundation\JsonResponse;
use Vlp\Mailer\Api\Admin\Dto\AdminApiDto;

class UpdateDataResult
{
    public function __construct(
        private readonly bool $status,
        private readonly ?AdminApiDto $data = null,
        private readonly array $errorMessage = [],
    ) {
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
        if (false === $this->status) {
            return new JsonResponse('Some error occured', 500);
        }

        if ($this->data instanceof AdminApiDto) {
            return new JsonResponse($this->data->toArray());
        }

        return new JsonResponse([]);
    }
}
