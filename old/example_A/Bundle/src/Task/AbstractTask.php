<?php

declare(strict_types=1);

namespace VLM\TaskWorkerBundle\Task;

use Psr\Log\LoggerInterface;

abstract class AbstractTask extends CronExpressionScheduledTask implements TaskInterface
{
    public function __construct(
        string $schedule,
        LoggerInterface $logger,
    ) {
        parent::__construct($schedule, $logger);
    }

    public function getName(): string
    {
        return static::class;
    }
}
