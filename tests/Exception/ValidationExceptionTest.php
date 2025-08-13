<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Tests\Exception;

use Freema\ReactAdminApiBundle\Exception\ValidationException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

class ValidationExceptionTest extends TestCase
{
    public function test_constructor_with_array_of_errors(): void
    {
        $errors = [
            'email' => 'Invalid email address',
            'name' => 'Name is required',
        ];

        $exception = new ValidationException($errors);

        $this->assertEquals('Validation failed', $exception->getMessage());
        $this->assertEquals($errors, $exception->getErrors());
        $this->assertNull($exception->getViolations());
    }

    public function test_constructor_with_custom_message(): void
    {
        $errors = ['field' => 'error'];
        $customMessage = 'Custom validation message';

        $exception = new ValidationException($errors, $customMessage);

        $this->assertEquals($customMessage, $exception->getMessage());
        $this->assertEquals($errors, $exception->getErrors());
    }

    public function test_constructor_with_constraint_violation_list(): void
    {
        $violations = new ConstraintViolationList([
            new ConstraintViolation(
                'Email is invalid',
                null,
                [],
                null,
                'email',
                'invalid@'
            ),
            new ConstraintViolation(
                'Name must be at least 3 characters',
                null,
                [],
                null,
                'name',
                'ab'
            ),
        ]);

        $exception = new ValidationException($violations);

        $this->assertEquals('Validation failed', $exception->getMessage());
        $this->assertSame($violations, $exception->getViolations());

        $errors = $exception->getErrors();
        
        // Check that we have the correct structure with detailed error information
        $this->assertIsArray($errors['email']);
        $this->assertIsArray($errors['name']);
        $this->assertEquals('Email is invalid', $errors['email']['message']);
        $this->assertEquals('Name must be at least 3 characters', $errors['name']['message']);
        $this->assertEquals('invalid@', $errors['email']['value']);
        $this->assertEquals('ab', $errors['name']['value']);
    }

    public function test_constructor_with_empty_violation_list(): void
    {
        $violations = new ConstraintViolationList([]);

        $exception = new ValidationException($violations);

        $this->assertEquals([], $exception->getErrors());
        $this->assertSame($violations, $exception->getViolations());
    }

    public function test_nested_property_paths(): void
    {
        $violations = new ConstraintViolationList([
            new ConstraintViolation(
                'Street is required',
                null,
                [],
                null,
                'address.street',
                null
            ),
            new ConstraintViolation(
                'Invalid postal code',
                null,
                [],
                null,
                'address.postalCode',
                '123'
            ),
        ]);

        $exception = new ValidationException($violations);

        $errors = $exception->getErrors();
        
        $this->assertIsArray($errors['address.street']);
        $this->assertIsArray($errors['address.postalCode']);
        $this->assertEquals('Street is required', $errors['address.street']['message']);
        $this->assertEquals('Invalid postal code', $errors['address.postalCode']['message']);
        $this->assertEquals('123', $errors['address.postalCode']['value']);
    }

    public function test_array_property_paths(): void
    {
        $violations = new ConstraintViolationList([
            new ConstraintViolation(
                'Invalid role',
                null,
                [],
                null,
                'roles[1]',
                'INVALID_ROLE'
            ),
            new ConstraintViolation(
                'Tag cannot be empty',
                null,
                [],
                null,
                'tags[0]',
                ''
            ),
        ]);

        $exception = new ValidationException($violations);

        $errors = $exception->getErrors();
        
        $this->assertIsArray($errors['roles[1]']);
        $this->assertIsArray($errors['tags[0]']);
        $this->assertEquals('Invalid role', $errors['roles[1]']['message']);
        $this->assertEquals('Tag cannot be empty', $errors['tags[0]']['message']);
        $this->assertEquals('INVALID_ROLE', $errors['roles[1]']['value']);
        $this->assertEquals('', $errors['tags[0]']['value']);
    }
}
