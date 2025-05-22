<?php

declare(strict_types=1);

namespace VLM\TaskWorkerBundle;

class TaskExecutionResult
{
    /** @var array<string, array{executed: bool, due: bool, error: ?string, output: array<string, mixed>}> */
    private array $results = [];

    /**
     * @param array<string, mixed> $output
     */
    public function addTaskResult(
        string $taskName,
        bool $isDue,
        bool $executed,
        array $output = [],
        ?string $error = null,
    ): void {
        $this->results[$taskName] = [
            'executed' => $executed,
            'due' => $isDue,
            'error' => $error,
            'output' => $output,
        ];
    }

    /**
     * @return array<string, array{executed: bool, due: bool, error: ?string, output: array<string, mixed>}>
     */
    public function getResults(): array
    {
        return $this->results;
    }

    public function getExecutedTasksCount(): int
    {
        return count(array_filter($this->results, fn (array $result) => $result['executed']));
    }

    public function getDueTasksCount(): int
    {
        return count(array_filter($this->results, fn (array $result) => $result['due']));
    }

    public function getFailedTasksCount(): int
    {
        return count(array_filter($this->results, fn (array $result) => null !== $result['error']));
    }
}
