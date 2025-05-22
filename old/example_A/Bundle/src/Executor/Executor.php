<?php

declare(strict_types=1);

namespace VLM\TaskWorkerBundle\Executor;

use Throwable;
use VLM\TaskWorkerBundle\Task\TaskInterface;

class Executor
{
    /**
     * @return array<string, mixed>
     */
    final public function execute(TaskInterface $task): array
    {
        try {
            $this->before();
            $output = $task->run();
            $this->after();
            $this->success();

            return $output;
        } catch (Throwable $t) {
            $this->onException($t);
            $this->fail();

            throw $t;
        }
    }

    protected function before(): void
    {
    }

    protected function success(): void
    {
    }

    protected function onException(Throwable $t): void
    {
    }

    protected function fail(): void
    {
    }

    protected function after(): void
    {
    }
}
