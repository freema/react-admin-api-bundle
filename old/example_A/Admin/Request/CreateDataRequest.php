<?php

declare(strict_types=1);

namespace Vlp\Mailer\Api\Admin\Request;

use Vlp\Mailer\Api\Admin\Dto\AdminApiDto;
use Vlp\Mailer\Api\Admin\Result\CreateDataResult;

class CreateDataRequest
{
    public function __construct(private readonly AdminApiDto $adminApiDto)
    {
    }

    public function getAdminApiDto(): AdminApiDto
    {
        return $this->adminApiDto;
    }

    public function createResult(bool $status, array $errorMessages = []): CreateDataResult
    {
        return new CreateDataResult($status, $errorMessages);
    }
}
