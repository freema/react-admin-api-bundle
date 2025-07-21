<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Exception;

use Symfony\Component\HttpFoundation\Response;

/**
 * Exception thrown when DTO creation fails
 */
class DtoCreationException extends ApiException
{
    public function __construct(string $dtoClass, string $reason)
    {
        parent::__construct(
            message: "Failed to create DTO '{$dtoClass}': {$reason}",
            code: Response::HTTP_BAD_REQUEST,
            apiCode: 'DTO_CREATION_FAILED'
        );
    }
}
