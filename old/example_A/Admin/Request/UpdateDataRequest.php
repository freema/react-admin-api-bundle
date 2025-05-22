<?php

declare(strict_types=1);

namespace Vlp\Mailer\Api\Admin\Request;

use Vlp\Mailer\Api\Admin\Dto\AdminApiDto;
use Vlp\Mailer\Api\Admin\Result\UpdateDataResult;

class UpdateDataRequest
{
    public function __construct(
        private readonly string $id,
        private readonly AdminApiDto $data,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getAdminApiDto(): AdminApiDto
    {
        return $this->data;
    }

    public function createResult(bool $status, ?AdminApiDto $data = null, array $errorMessages = []): UpdateDataResult
    {
        return new UpdateDataResult($status, $data, $errorMessages);
    }
}
