<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Exception;

use Symfony\Component\HttpFoundation\Response;

/**
 * Exception thrown when a DTO class is not found
 */
class DtoClassNotFoundException extends ApiException
{
    public function __construct(string $dtoClass)
    {
        parent::__construct(
            message: "DTO class '{$dtoClass}' does not exist. Please check the class name and make sure it's properly loaded.",
            code: Response::HTTP_INTERNAL_SERVER_ERROR,
            apiCode: 'DTO_CLASS_NOT_FOUND'
        );
    }
}
