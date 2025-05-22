<?php

declare(strict_types=1);

namespace VLM\TaskWorkerBundle;

use Psr\Log\LoggerInterface;
use Throwable;
use Traversable;
use VLM\TaskWorkerBundle\Executor\Executor;
use VLM\TaskWorkerBundle\Task\TaskInterface;
use VLM\TaskWorkerBundle\Task\UnregisteredTaskRequestedException;

class TaskWorker implements TaskWorkerInterface
{
    /** @var TaskInterface[] */
    private array $tasks = [];

    private Executor $executor;

    public function __construct(
        private readonly LoggerInterface $logger,
        ?Executor $executor = null,
    ) {
        $this->executor = $executor ?? new Executor();
    }

    public function register(TaskInterface $task): void
    {
        $this->tasks[$task->getName()] = $task;
    }

    /**
     * @param Traversable<TaskInterface> $iterator
     */
    public function registerAll(Traversable $iterator): void
    {
        foreach ($iterator as $task) {
            $this->register($task);
        }
    }

    /**
     * @return array<string, mixed>
     *
     * @throws UnregisteredTaskRequestedException
     */
    public function run(string $name): array
    {
        if (!isset($this->tasks[$name])) {
            $this->logger->error(sprintf('TaskWorker has no task with name %s registered', $name));

            throw new UnregisteredTaskRequestedException(sprintf('TaskWorker has no task with name %s registered', $name));
        }

        return $this->executor->execute($this->tasks[$name]);
    }

    public function runAll(): TaskExecutionResult
    {
        $result = new TaskExecutionResult();

        foreach ($this->tasks as $task) {
            $isDue = $task->isDue();
            $executed = false;
            $error = null;
            $output = [];

            if ($isDue) {
                try {
                    $output = $this->executor->execute($task);
                    $executed = true;
                    $this->logger->info(sprintf('Task %s executed successfully', $task->getName()));
                } catch (Throwable $e) {
                    $error = $e->getMessage();
                    $this->logger->error(sprintf('Task %s failed: %s', $task->getName(), $e->getMessage()), [
                        'exception' => $e,
                    ]);
                }
            }

            $result->addTaskResult($task->getName(), $isDue, $executed, $output, $error);
        }

        return $result;
    }
}
