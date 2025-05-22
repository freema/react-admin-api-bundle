<?php

declare(strict_types=1);

namespace VLM\TaskWorkerBundle;

use VLM\TaskWorkerBundle\Executor\Executor;
use VLM\TaskWorkerBundle\Task\TaskInterface;

class Job implements JobInterface
{
    public function __construct(private readonly TaskInterface $task)
    {
    }

    public function start(Executor $executor): void
    {
        $executor->execute($this->task);
    }
}
