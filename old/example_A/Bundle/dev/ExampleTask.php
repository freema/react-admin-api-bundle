<?php

declare(strict_types=1);

namespace VLM\TaskWorkerBundleDev;

use Psr\Log\LoggerInterface;
use VLM\TaskWorkerBundle\Task\AbstractTask;

class ExampleTask extends AbstractTask
{
    public function __construct(
        string $schedule,
        LoggerInterface $logger,
        private int $usersCount = 100,
    ) {
        parent::__construct($schedule, $logger);
    }

    public function getName(): string
    {
        return static::class;
    }

    public function run(): array
    {
        $processedUsers = rand(1, $this->usersCount);
        $errors = rand(0, 5);
        $timeSpent = rand(1, 60);

        return [
            'status' => 'completed',
            'statistics' => [
                'processed_users' => $processedUsers,
                'errors_count' => $errors,
                'time_spent_seconds' => $timeSpent,
            ],
            'details' => sprintf(
                'Zpracováno %d uživatelů za %d sekund s %d chybami',
                $processedUsers,
                $timeSpent,
                $errors,
            ),
        ];
    }
}
