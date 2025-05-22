<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Request;

use Freema\ReactAdminApiBundle\Result\DeleteDataResult;
use Symfony\Component\HttpFoundation\Request;

/**
 * Represents a request to delete multiple entities.
 */
class DeleteManyDataRequest
{
    /** @var array<string|int> */
    private array $ids = [];

    public function __construct(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        if (isset($data['ids']) && is_array($data['ids'])) {
            $this->ids = $data['ids'];
        }
    }

    /**
     * @return array<string|int>
     */
    public function getIds(): array
    {
        return $this->ids;
    }

    /**
     * Create a result from this request.
     *
     * @param bool $status Whether the operation was successful
     * @param array<string> $errorMessages Error messages if the operation failed
     */
    public function createResult(bool $status = true, array $errorMessages = []): DeleteDataResult
    {
        return new DeleteDataResult($status, $errorMessages);
    }
}