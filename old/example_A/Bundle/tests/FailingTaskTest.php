<?php

declare(strict_types=1);

namespace VLM\TaskWorkerBundle\Tests;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use VLM\TaskWorkerBundle\TaskWorkerInterface;
use VLM\TaskWorkerBundle\Tests\Fixtures\FailingTask;

/**
 * @internal
 */
class FailingTaskTest extends KernelTestCase
{
    private TaskWorkerInterface $taskWorker;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $this->taskWorker = $container->get(TaskWorkerInterface::class);

        // Registrujeme failing task pro testy
        $logger = $container->get(LoggerInterface::class);
        $failingTask = new FailingTask('* * * * *', $logger);
        $container->get(TaskWorkerInterface::class)->register($failingTask);
    }

    public function testFailingTaskExecution(): void
    {
        $result = $this->taskWorker->runAll();

        // Kontrola počtů
        $this->assertGreaterThan(0, $result->getFailedTasksCount(), 'Měl by být alespoň jeden selhaný task');

        // Kontrola výsledků selhaného tasku
        $results = $result->getResults();
        $this->assertArrayHasKey('failing_task', $results);

        $taskResult = $results['failing_task'];
        $this->assertTrue($taskResult['due']);
        $this->assertFalse($taskResult['executed']);
        $this->assertNotNull($taskResult['error']);
        $this->assertStringContainsString('Simulovaná chyba', $taskResult['error']);
    }
}
