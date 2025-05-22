<?php

declare(strict_types=1);

namespace Vlp\Mailer\Api\Admin\Exception;

class ValidationException extends \Exception
{
    public function __construct(private array $errorMessages = [])
    {
    }

    public function addErrorMessage(string $field, string $message): static
    {
        $this->errorMessages[$field] = $message;

        return $this;
    }

    public function getErrorMessages(): array
    {
        return $this->errorMessages;
    }
}
