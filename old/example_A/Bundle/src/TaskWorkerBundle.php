<?php

declare(strict_types=1);

namespace VLM\TaskWorkerBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use VLM\TaskWorkerBundle\DependencyInjection\TaskWorkerExtension;

class TaskWorkerBundle extends Bundle
{
    public function getContainerExtension(): TaskWorkerExtension
    {
        if (null === $this->extension) {
            $this->extension = new TaskWorkerExtension();
        }

        return $this->extension;
    }
}
