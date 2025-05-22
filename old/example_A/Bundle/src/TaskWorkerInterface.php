<?php

declare(strict_types=1);

namespace VLM\TaskWorkerBundle;

interface TaskWorkerInterface
{
    /**
     * @return array<string, mixed>
     */
    public function run(string $name): array;

    public function runAll(): TaskExecutionResult;
}
