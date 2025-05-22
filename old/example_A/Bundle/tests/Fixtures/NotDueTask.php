<?php

declare(strict_types=1);

namespace VLM\TaskWorkerBundle\Tests\Fixtures;

use VLM\TaskWorkerBundle\Task\AbstractTask;

class NotDueTask extends AbstractTask
{
    public function getName(): string
    {
        return 'not_due_task';
    }

    public function isDue(): bool
    {
        return false;
    }

    public function run(): array
    {
        return [
            'status' => 'success',
            'message' => 'Tento task by se nikdy neměl spustit',
        ];
    }
}
