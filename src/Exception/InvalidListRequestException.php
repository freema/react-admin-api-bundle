<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Exception;

/**
 * Thrown when a list request filters or sorts on a field the resource does not
 * allow, or asks for an unknown sort direction. ApiExceptionListener answers it
 * with 400 Bad Request, like any \InvalidArgumentException.
 */
class InvalidListRequestException extends \InvalidArgumentException
{
    public static function filterField(string $field): self
    {
        return new self(sprintf('Filtering by "%s" is not allowed.', $field));
    }

    public static function sortField(string $field): self
    {
        return new self(sprintf('Sorting by "%s" is not allowed.', $field));
    }

    public static function sortOrder(string $order): self
    {
        return new self(sprintf('Sort order "%s" is not allowed, use ASC or DESC.', $order));
    }
}
