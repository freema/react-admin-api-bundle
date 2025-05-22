<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Exception;

class ValidationException extends \Exception
{
    /**
     * @param array<string, string> $errors
     */
    public function __construct(private array $errors)
    {
        parent::__construct('Validation failed');
    }

    /**
     * @return array<string, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}