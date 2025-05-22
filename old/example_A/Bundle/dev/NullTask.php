<?php

declare(strict_types=1);

namespace VLM\TaskWorkerBundleDev;

use Psr\Log\LoggerInterface;
use VLM\TaskWorkerBundle\Task\CronExpressionScheduledTask;
use VLM\TaskWorkerBundle\Task\TaskInterface;

class NullTask extends CronExpressionScheduledTask implements TaskInterface
{
    public function __construct(
        string $schedule,
        LoggerInterface $logger,
    ) {
        parent::__construct($schedule, $logger);
    }

    public function getName(): string
    {
        return 'NullTask';
    }

    /**
     * @return array<string, mixed>
     */
    public function run(): array
    {
        $timestamp = date('Y-m-d H:i:s');
        $this->logger->info('NullTask executed at ' . $timestamp);

        return [
            'executed_at' => $timestamp,
            'status' => 'success',
        ];
    }
}
