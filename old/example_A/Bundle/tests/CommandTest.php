<?php

declare(strict_types=1);

namespace VLM\TaskWorkerBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use VLM\TaskWorkerBundle\Command\RunCommand;
use VLM\TaskWorkerBundle\TaskWorkerInterface;

/**
 * @internal
 */
class CommandTest extends KernelTestCase
{
    private CommandTester $commandTester;

    private TaskWorkerInterface $taskWorker;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = self::getContainer();

        $this->taskWorker = $container->get(TaskWorkerInterface::class);
        $command = $container->get(RunCommand::class);

        $this->commandTester = new CommandTester($command);
    }

    public function testTaskExecution(): void
    {
        $exitCode = $this->commandTester->execute([]);

        $this->assertSame(0, $exitCode);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('Starting task worker...', $output);
        $this->assertStringContainsString('Execution Summary:', $output);
    }
}
