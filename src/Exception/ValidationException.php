<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Exception;

use Symfony\Component\Validator\ConstraintViolationListInterface;

class ValidationException extends \Exception
{
    private array $errors = [];
    private ?ConstraintViolationListInterface $violations = null;

    /**
     * @param array<string, string>|ConstraintViolationListInterface $errors
     */
    public function __construct($errors, string $message = 'Validation failed')
    {
        if ($errors instanceof ConstraintViolationListInterface) {
            $this->violations = $errors;
            $this->errors = $this->violationsToArray($errors);
        } else {
            $this->errors = $errors;
        }

        parent::__construct($message);
    }

    /**
     * @return array<string, string|array>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getViolations(): ?ConstraintViolationListInterface
    {
        return $this->violations;
    }

    /**
     * Convert violations to array format
     *
     * @param ConstraintViolationListInterface $violations
     *
     * @return array<string, string|array>
     */
    private function violationsToArray(ConstraintViolationListInterface $violations): array
    {
        $errors = [];

        foreach ($violations as $violation) {
            $propertyPath = $violation->getPropertyPath();
            $message = $violation->getMessage();

            // Add context to the error message
            $invalidValue = $violation->getInvalidValue();
            $parameters = $violation->getParameters();

            // Build a more descriptive error message
            $detailedMessage = [
                'message' => $message,
                'field' => $propertyPath,
                'value' => $invalidValue,
            ];

            // Add constraint-specific information
            if (isset($parameters['{{ choices }}'])) {
                $detailedMessage['allowed_values'] = $parameters['{{ choices }}'];
            }

            if (isset($parameters['{{ value }}'])) {
                $detailedMessage['rejected_value'] = $parameters['{{ value }}'];
            }

            // If there are multiple violations for the same field, collect them in an array
            if (!isset($errors[$propertyPath])) {
                $errors[$propertyPath] = $detailedMessage;
            } else {
                // Convert to array if it's a single detailed message
                if (isset($errors[$propertyPath]['message'])) {
                    $errors[$propertyPath] = [$errors[$propertyPath]];
                }
                $errors[$propertyPath][] = $detailedMessage;
            }
        }

        return $errors;
    }
}
