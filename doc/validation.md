# Validation

This document explains how to implement and use validation in the ReactAdminApiBundle.

## Overview

The bundle integrates with Symfony's validation component to validate DTO objects before they are processed. Validation is automatically triggered during create and update operations.

## Implementing Validation on DTOs

To add validation to your DTOs, use Symfony's validation constraints as attributes or annotations:

```php
<?php

namespace App\Dto;

use Freema\ReactAdminApiBundle\Interface\DtoInterface;
use Symfony\Component\Validator\Constraints as Assert;

class UserDto implements DtoInterface
{
    public ?int $id = null;
    
    #[Assert\NotBlank(message: 'Name is required')]
    #[Assert\Length(
        min: 3, 
        max: 255,
        minMessage: 'Name must be at least {{ limit }} characters long',
        maxMessage: 'Name cannot be longer than {{ limit }} characters'
    )]
    public ?string $name = null;
    
    #[Assert\NotBlank(message: 'Email is required')]
    #[Assert\Email(message: 'Please provide a valid email address')]
    public ?string $email = null;
    
    #[Assert\NotNull(message: 'Active status must be specified')]
    public ?bool $active = null;
    
    #[Assert\Choice(
        choices: ['ROLE_USER', 'ROLE_ADMIN', 'ROLE_MANAGER'],
        multiple: true,
        message: 'Invalid role specified'
    )]
    public array $roles = ['ROLE_USER'];
    
    // ... rest of the DTO implementation
}
```

## Validation Process

The validation process works as follows:

1. When a POST (create) or PUT (update) request is received, the controller converts the JSON data to a DTO using `DtoFactory`
2. The DTO is validated using Symfony's `ValidatorInterface`
3. If validation fails, a `ValidationException` is thrown
4. The `ApiExceptionListener` catches the exception and returns a structured error response

## Error Response Format

When validation fails, the API returns a structured error response compatible with React Admin:

```json
{
  "error": "VALIDATION_ERROR",
  "message": "Validation failed",
  "errors": {
    "email": "Please provide a valid email address.",
    "name": "Name is required",
    "roles[1]": "Invalid role specified"
  }
}
```

The HTTP status code for validation errors is `400 Bad Request`.

## Common Validation Constraints

Here are some commonly used validation constraints:

### String Validation

```php
#[Assert\NotBlank]
#[Assert\Length(min: 3, max: 100)]
#[Assert\Regex(pattern: '/^[a-zA-Z0-9]+$/')]
public ?string $username = null;
```

### Email Validation

```php
#[Assert\NotBlank]
#[Assert\Email]
public ?string $email = null;
```

### Number Validation

```php
#[Assert\NotNull]
#[Assert\Positive]
#[Assert\LessThan(100)]
public ?int $age = null;
```

### Date Validation

```php
#[Assert\NotNull]
#[Assert\GreaterThan('today')]
public ?\DateTimeInterface $scheduledAt = null;
```

### Collection Validation

```php
#[Assert\Count(min: 1, max: 5)]
#[Assert\All([
    new Assert\NotBlank(),
    new Assert\Length(min: 2)
])]
public array $tags = [];
```

### Custom Validation

You can also create custom validation constraints:

```php
#[Assert\Callback]
public function validate(ExecutionContextInterface $context): void
{
    if ($this->startDate && $this->endDate && $this->startDate > $this->endDate) {
        $context->buildViolation('End date must be after start date')
            ->atPath('endDate')
            ->addViolation();
    }
}
```

## Conditional Validation

For conditional validation based on other fields:

```php
#[Assert\Expression(
    expression: 'this.type != "premium" or this.price > 0',
    message: 'Premium items must have a price greater than 0'
)]
public ?float $price = null;
```

## Validation Groups

You can use validation groups to apply different validation rules in different contexts:

```php
#[Assert\NotBlank(groups: ['create'])]
#[Assert\Length(min: 8, groups: ['create', 'password_change'])]
public ?string $password = null;
```

To use validation groups, you would need to modify the controller to specify which groups to validate.

## Testing Validation

Here's an example of testing validation in your application:

```bash
# Test with invalid data
curl -X POST http://localhost:8080/api/users \
  -H "Content-Type: application/json" \
  -d '{"name": "a", "email": "invalid-email", "active": null}'

# Expected response:
# {
#   "error": "VALIDATION_ERROR",
#   "message": "Validation failed",
#   "errors": {
#     "name": "Name must be at least 3 characters long",
#     "email": "Please provide a valid email address.",
#     "active": "Active status must be specified"
#   }
# }
```

## React Admin Integration

React Admin automatically handles validation errors returned by the API. When a validation error occurs:

1. The form submission is cancelled
2. Error messages are displayed next to the corresponding fields
3. The user can correct the errors and resubmit

No special configuration is needed on the React Admin side - it works out of the box with the bundle's error format.

## Best Practices

1. **Always validate required fields** - Use `#[Assert\NotBlank]` or `#[Assert\NotNull]` for required fields
2. **Provide meaningful error messages** - Customize messages to help users understand what went wrong
3. **Validate data types** - Ensure data types match what your application expects
4. **Use appropriate constraints** - Choose constraints that match your business rules
5. **Test edge cases** - Test with empty values, extreme values, and invalid formats
6. **Keep validation DRY** - Consider creating custom constraint classes for complex repeated validations

## Advanced Usage

### Validating Nested Objects

For DTOs with nested objects:

```php
class AddressDto
{
    #[Assert\NotBlank]
    public ?string $street = null;
    
    #[Assert\NotBlank]
    public ?string $city = null;
}

class UserDto implements DtoInterface
{
    #[Assert\Valid]
    public ?AddressDto $address = null;
}
```

### Collection of Objects

For validating collections of nested objects:

```php
#[Assert\All([
    new Assert\Type(OrderItemDto::class)
])]
#[Assert\Valid]
public array $items = [];
```

This ensures each item in the collection is validated according to its own constraints.