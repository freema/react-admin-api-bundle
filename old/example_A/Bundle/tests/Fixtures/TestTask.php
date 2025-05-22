<?php

declare(strict_types=1);

namespace VLM\TaskWorkerBundle\Tests\Fixtures;

use VLM\TaskWorkerBundle\Task\AbstractTask;

class TestTask extends AbstractTask
{
    private bool $hasRun = false;

    public function getName(): string
    {
        return 'test_task';
    }

    public function run(): array
    {
        $this->hasRun = true;

        return [
            'status' => 'success',
            'timestamp' => time(),
        ];
    }

    public function hasRun(): bool
    {
        return $this->hasRun;
    }
}
