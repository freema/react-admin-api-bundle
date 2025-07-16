<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Tests\Exception;

use Freema\ReactAdminApiBundle\Exception\ValidationException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

class ValidationExceptionTest extends TestCase
{
    public function testConstructorWithArrayOfErrors(): void
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
    
    public function testConstructorWithCustomMessage(): void
    {
        $errors = ['field' => 'error'];
        $customMessage = 'Custom validation message';
        
        $exception = new ValidationException($errors, $customMessage);
        
        $this->assertEquals($customMessage, $exception->getMessage());
        $this->assertEquals($errors, $exception->getErrors());
    }
    
    public function testConstructorWithConstraintViolationList(): void
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
        
        $expectedErrors = [
            'email' => 'Email is invalid',
            'name' => 'Name must be at least 3 characters',
        ];
        $this->assertEquals($expectedErrors, $exception->getErrors());
    }
    
    public function testConstructorWithEmptyViolationList(): void
    {
        $violations = new ConstraintViolationList([]);
        
        $exception = new ValidationException($violations);
        
        $this->assertEquals([], $exception->getErrors());
        $this->assertSame($violations, $exception->getViolations());
    }
    
    public function testNestedPropertyPaths(): void
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
        
        $expectedErrors = [
            'address.street' => 'Street is required',
            'address.postalCode' => 'Invalid postal code',
        ];
        $this->assertEquals($expectedErrors, $exception->getErrors());
    }
    
    public function testArrayPropertyPaths(): void
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
        
        $expectedErrors = [
            'roles[1]' => 'Invalid role',
            'tags[0]' => 'Tag cannot be empty',
        ];
        $this->assertEquals($expectedErrors, $exception->getErrors());
    }
}