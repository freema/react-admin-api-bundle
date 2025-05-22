<?php

declare(strict_types=1);

namespace VLM\TaskWorkerBundle\Task;

interface TaskInterface
{
    public function getName(): string;

    public function isDue(): bool;

    /**
     * @return array<string, mixed>
     */
    public function run(): array;
}
