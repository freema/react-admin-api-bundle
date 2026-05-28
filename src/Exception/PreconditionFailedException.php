<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Thrown by ResourceController when an `If-Match` header is present and does not match
 * the current ETag of the target entity. Mapped to HTTP 412 by the exception listener.
 */
final class PreconditionFailedException extends HttpException
{
    public function __construct(string $message = 'Precondition Failed (ETag mismatch).', ?\Throwable $previous = null)
    {
        parent::__construct(412, $message, $previous);
    }
}
