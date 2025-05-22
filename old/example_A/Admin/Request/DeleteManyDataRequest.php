<?php
declare(strict_types=1);

namespace Vlp\Mailer\Api\Admin\Request;

use Symfony\Component\HttpFoundation\Request;
use Vlp\Mailer\Api\Admin\Result\DeleteDataResult;

class DeleteManyDataRequest
{
    private array $ids = [];

    public function __construct(
        Request $request,
    ) {
        $filter = $request->get('filter');
        if (null !== $filter) {
            $filter = json_decode($filter, true);
            if (isset($filter['id'])) {
                $this->ids = $filter['id'];
            }
        }
    }

    public function getIds(): array
    {
        return $this->ids;
    }

    public function createResult(bool $status, array $errorMessages = []): DeleteDataResult
    {
        return new DeleteDataResult($status, $errorMessages);
    }
}
