<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Tests\EventListener;

use Freema\ReactAdminApiBundle\EventListener\ApiExceptionListener;
use Freema\ReactAdminApiBundle\Exception\ValidationException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

class ApiExceptionListenerValidationTest extends TestCase
{
    private ApiExceptionListener $listener;
    private RouterInterface $router;
    private HttpKernelInterface $kernel;

    protected function setUp(): void
    {
        $this->router = $this->createMock(RouterInterface::class);
        $this->kernel = $this->createMock(HttpKernelInterface::class);

        $this->listener = new ApiExceptionListener(
            true, // enabled
            false // debug mode off
        );
    }

    public function test_handle_validation_exception_with_array_errors(): void
    {
        $errors = [
            'email' => 'Invalid email address',
            'name' => 'Name is required',
            'roles[0]' => 'Invalid role specified',
        ];

        $exception = new ValidationException($errors);
        $request = $this->createRequestWithRoute('react_admin_api_resource_create');

        $event = new ExceptionEvent(
            $this->kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $exception
        );

        $this->listener->onKernelException($event);

        $response = $event->getResponse();
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertEquals('VALIDATION_ERROR', $content['error']);
        $this->assertEquals('Validation failed', $content['message']);
        $this->assertEquals($errors, $content['errors']);
    }

    public function test_handle_validation_exception_with_constraint_violations(): void
    {
        $violations = new ConstraintViolationList([
            new ConstraintViolation(
                'Email is invalid',
                null,
                [],
                null,
                'email',
                'not-an-email'
            ),
            new ConstraintViolation(
                'Name must be at least 3 characters',
                null,
                [],
                null,
                'name',
                'ab'
            ),
            new ConstraintViolation(
                'Invalid role',
                null,
                [],
                null,
                'roles[1]',
                'INVALID_ROLE'
            ),
        ]);

        $exception = new ValidationException($violations);
        $request = $this->createRequestWithRoute('react_admin_api_resource_update');

        $event = new ExceptionEvent(
            $this->kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $exception
        );

        $this->listener->onKernelException($event);

        $response = $event->getResponse();
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertEquals('VALIDATION_ERROR', $content['error']);
        $this->assertEquals('Validation failed', $content['message']);

        $expectedErrors = [
            'email' => 'Email is invalid',
            'name' => 'Name must be at least 3 characters',
            'roles[1]' => 'Invalid role',
        ];
        $this->assertEquals($expectedErrors, $content['errors']);
    }

    public function test_handle_validation_exception_with_debug_mode(): void
    {
        // Create listener with debug mode enabled
        $listener = new ApiExceptionListener(
            true, // enabled
            true  // debug mode on
        );

        $errors = ['field' => 'error message'];
        $exception = new ValidationException($errors);
        $request = $this->createRequestWithRoute('react_admin_api_resource_create');

        $event = new ExceptionEvent(
            $this->kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $exception
        );

        $listener->onKernelException($event);

        $response = $event->getResponse();
        $content = json_decode($response->getContent(), true);

        // In debug mode, we should still get the same validation error structure
        $this->assertEquals('VALIDATION_ERROR', $content['error']);
        $this->assertEquals('Validation failed', $content['message']);
        $this->assertEquals($errors, $content['errors']);

        // Debug info is not added for validation errors as they already have detailed information
        $this->assertArrayNotHasKey('debug', $content);
    }

    public function test_ignore_validation_exception_for_non_api_routes(): void
    {
        $exception = new ValidationException(['field' => 'error']);
        $request = $this->createRequestWithRoute('some_other_route');

        $event = new ExceptionEvent(
            $this->kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $exception
        );

        $this->listener->onKernelException($event);

        // Response should not be set for non-API routes
        $this->assertNull($event->getResponse());
    }

    public function test_handle_nested_property_path_errors(): void
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
            new ConstraintViolation(
                'Price must be greater than 0',
                null,
                [],
                null,
                'items[0].price',
                -10
            ),
        ]);

        $exception = new ValidationException($violations);
        $request = $this->createRequestWithRoute('react_admin_api_resource_create');

        $event = new ExceptionEvent(
            $this->kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $exception
        );

        $this->listener->onKernelException($event);

        $response = $event->getResponse();
        $content = json_decode($response->getContent(), true);

        $expectedErrors = [
            'address.street' => 'Street is required',
            'address.postalCode' => 'Invalid postal code',
            'items[0].price' => 'Price must be greater than 0',
        ];
        $this->assertEquals($expectedErrors, $content['errors']);
    }

    public function test_disabled_listener(): void
    {
        // Create listener that is disabled
        $listener = new ApiExceptionListener(
            false, // disabled
            false
        );

        $exception = new ValidationException(['field' => 'error']);
        $request = $this->createRequestWithRoute('react_admin_api_resource_create');

        $event = new ExceptionEvent(
            $this->kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $exception
        );

        $listener->onKernelException($event);

        // Response should not be set when listener is disabled
        $this->assertNull($event->getResponse());
    }

    private function createRequestWithRoute(string $routeName): Request
    {
        $request = new Request();
        $request->attributes->set('_route', $routeName);

        return $request;
    }
}
