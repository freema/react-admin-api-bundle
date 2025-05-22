<?php

declare(strict_types=1);

namespace VLM\TaskWorkerBundle;

use VLM\TaskWorkerBundle\Executor\Executor;

interface JobInterface
{
    public function start(Executor $executor): void;
}
