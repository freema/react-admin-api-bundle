<?php
declare(strict_types=1);

namespace Vlp\Mailer\Api\Admin\Security;

class ResourceOwnerException extends \RuntimeException
{
    public function __construct(string $message = 'Resource owner not found', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
