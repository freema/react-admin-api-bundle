<?php
declare(strict_types=1);

namespace Vlp\Mailer\Api\Admin\Request;

use Vlp\Mailer\Api\Admin\Result\DeleteDataResult;

class DeleteDataRequest
{
    public function __construct(
        private readonly string $id,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function createResult(bool $status, array $errorMessages = []): DeleteDataResult
    {
        return new DeleteDataResult($status, $errorMessages);
    }
}
