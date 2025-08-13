<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Exception;

class AdminAccessDeniedException extends \Exception
{
    public function __construct(string $message = 'Access denied. Admin privileges required.')
    {
        parent::__construct($message);
    }
}
