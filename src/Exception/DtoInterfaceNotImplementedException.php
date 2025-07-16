<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Exception;

use Symfony\Component\HttpFoundation\Response;

/**
 * Exception thrown when a DTO class doesn't implement DtoInterface
 */
class DtoInterfaceNotImplementedException extends ApiException
{
    public function __construct(string $dtoClass)
    {
        parent::__construct(
            message: "Class '{$dtoClass}' must implement DtoInterface. Please make sure your DTO class extends AdminApiDto or implements DtoInterface.",
            code: Response::HTTP_INTERNAL_SERVER_ERROR,
            apiCode: 'DTO_INTERFACE_NOT_IMPLEMENTED'
        );
    }
}