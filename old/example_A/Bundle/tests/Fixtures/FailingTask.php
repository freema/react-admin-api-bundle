<?php

declare(strict_types=1);

namespace VLM\TaskWorkerBundle\Tests\Fixtures;

use RuntimeException;
use VLM\TaskWorkerBundle\Task\AbstractTask;

class FailingTask extends AbstractTask
{
    public function getName(): string
    {
        return 'failing_task';
    }

    public function run(): array
    {
        throw new RuntimeException('Simulovaná chyba tasku');
    }
}
