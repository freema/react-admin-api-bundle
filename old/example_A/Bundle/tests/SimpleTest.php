<?php

declare(strict_types=1);

namespace VLM\TaskWorkerBundle\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use VLM\TaskWorkerBundle\Task\AbstractTask;
use VLM\TaskWorkerBundle\Tests\Fixtures\TestTask;

/**
 * @internal
 */
class SimpleTest extends TestCase
{
    public function testBasicTaskCreation(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $task = new TestTask('* * * * *', $logger);

        $this->assertInstanceOf(AbstractTask::class, $task);
        $this->assertSame('test_task', $task->getName());
        $this->assertTrue($task->isDue());
    }

    public function testTaskExecution(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $task = new TestTask('* * * * *', $logger);

        $result = $task->run();

        $this->assertArrayHasKey('status', $result);
        $this->assertSame('success', $result['status']);
        $this->assertArrayHasKey('timestamp', $result);
        $this->assertTrue($task->hasRun());
    }
}
