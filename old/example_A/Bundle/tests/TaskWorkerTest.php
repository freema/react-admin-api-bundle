<?php

declare(strict_types=1);

namespace VLM\TaskWorkerBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use VLM\TaskWorkerBundle\Task\UnregisteredTaskRequestedException;
use VLM\TaskWorkerBundle\TaskExecutionResult;
use VLM\TaskWorkerBundle\TaskWorkerInterface;

/**
 * @internal
 */
class TaskWorkerTest extends KernelTestCase
{
    private TaskWorkerInterface $taskWorker;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $this->taskWorker = $container->get(TaskWorkerInterface::class);
    }

    public function testRunAll(): void
    {
        $result = $this->taskWorker->runAll();

        $this->assertInstanceOf(TaskExecutionResult::class, $result);
        $this->assertSame(1, $result->getDueTasksCount(), 'Měl by být přesně jeden due task');
        $this->assertSame(1, $result->getExecutedTasksCount(), 'Měl by být přesně jeden provedený task');
        $this->assertSame(0, $result->getFailedTasksCount(), 'Neměly by být žádné selhané tasky');
    }

    public function testTaskResults(): void
    {
        $result = $this->taskWorker->runAll();
        $results = $result->getResults();

        $this->assertArrayHasKey('test_task', $results);

        $taskResult = $results['test_task'];
        $this->assertTrue($taskResult['executed']);
        $this->assertTrue($taskResult['due']);
        $this->assertNull($taskResult['error']);
        $this->assertIsArray($taskResult['output']);

        $this->assertArrayHasKey('status', $taskResult['output']);
        $this->assertArrayHasKey('timestamp', $taskResult['output']);
        $this->assertSame('success', $taskResult['output']['status']);
    }

    public function testRunSpecificTask(): void
    {
        $output = $this->taskWorker->run('test_task');

        $this->assertIsArray($output);
        $this->assertArrayHasKey('status', $output);
        $this->assertArrayHasKey('timestamp', $output);
        $this->assertSame('success', $output['status']);
    }

    public function testInvalidTaskExecution(): void
    {
        $this->expectException(UnregisteredTaskRequestedException::class);
        $this->expectExceptionMessage('TaskWorker has no task with name non_existent_task registered');

        $this->taskWorker->run('non_existent_task');
    }
}
