<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Tests\Exception;

use Freema\ReactAdminApiBundle\Exception\AdminAccessDeniedException;
use PHPUnit\Framework\TestCase;

class AdminAccessDeniedExceptionTest extends TestCase
{
    public function test_default_message(): void
    {
        $exception = new AdminAccessDeniedException();

        $this->assertSame('Access denied. Admin privileges required.', $exception->getMessage());
    }

    public function test_custom_message(): void
    {
        $customMessage = 'Custom access denied message';
        $exception = new AdminAccessDeniedException($customMessage);

        $this->assertSame($customMessage, $exception->getMessage());
    }

    public function test_is_exception(): void
    {
        $exception = new AdminAccessDeniedException();

        $this->assertInstanceOf(\Exception::class, $exception);
    }
}
