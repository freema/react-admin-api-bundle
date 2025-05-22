<?php

declare(strict_types=1);

namespace VLM\TaskWorkerBundle\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use VLM\TaskWorkerBundle\TaskWorkerInterface;

class RunCommand extends Command
{
    public function __construct(
        private readonly TaskWorkerInterface $taskWorker,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct('task-worker:run');

        $this->setDescription('Run scheduled tasks');
        $this->setHelp('This command finds all tasks tagged with "taskWorker.task" and runs them according to their schedule.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $output->writeln('<info>Starting task worker...</info>');

            $result = $this->taskWorker->runAll();

            // Output summary
            $output->writeln([
                '',
                '<info>Execution Summary:</info>',
                sprintf('Total due tasks: %d', $result->getDueTasksCount()),
                sprintf('Successfully executed: %d', $result->getExecutedTasksCount()),
                sprintf('Failed tasks: %d', $result->getFailedTasksCount()),
                '',
            ]);

            // Output detailed results with task output
            foreach ($result->getResults() as $taskName => $taskResult) {
                if ($taskResult['due']) {
                    if ($taskResult['executed']) {
                        $output->writeln(sprintf('<info>✓ %s: Successfully executed</info>', $taskName));

                        // Display task output
                        if (!empty($taskResult['output'])) {
                            $output->writeln('  Task output:');
                            foreach ($taskResult['output'] as $key => $value) {
                                if (is_array($value)) {
                                    $output->writeln(sprintf('    %s:', $key));
                                    foreach ($value as $subKey => $subValue) {
                                        $output->writeln(sprintf('      %s: %s', $subKey, $this->formatValue($subValue)));
                                    }
                                } else {
                                    $output->writeln(sprintf('    %s: %s', $key, $this->formatValue($value)));
                                }
                            }
                        }
                    } else {
                        $output->writeln(sprintf('<error>✗ %s: Failed - %s</error>', $taskName, $this->formatValue($taskResult['error'])));
                    }
                } else {
                    $output->writeln(sprintf('⏳ %s: Not due for execution', $taskName));
                }
            }

            return Command::SUCCESS;
        } catch (Throwable $e) {
            $this->logger->error('Error during task execution: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            $output->writeln('<error>Error occurred: ' . $e->getMessage() . '</error>');

            return Command::FAILURE;
        }
    }

    private function formatValue(mixed $value): string
    {
        if (is_scalar($value) || is_null($value)) {
            return (string) $value;
        }

        return '[non-scalar value]';
    }
}
