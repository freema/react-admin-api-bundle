<?php

declare(strict_types=1);

namespace VLM\TaskWorkerBundle\Task;

use Cron\CronExpression;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

abstract class CronExpressionScheduledTask
{
    public function __construct(
        protected string $schedule,
        protected LoggerInterface $logger,
    ) {
        if (!CronExpression::isValidExpression($schedule)) {
            throw new InvalidArgumentException(sprintf('Invalid cron expression: %s', $schedule));
        }
    }

    public function isDue(): bool
    {
        try {
            return (new CronExpression($this->schedule))->isDue();
        } catch (InvalidArgumentException $e) {
            $this->logger->error(sprintf('CronExpressionScheduledTask invalid schedule format: %s', $e->getMessage()));

            return false;
        }
    }
}
