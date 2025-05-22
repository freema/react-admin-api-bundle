<?php

declare(strict_types=1);

use Symfony\Component\Console\Application;
use VLM\TaskWorkerBundle\Command\RunCommand;
use VLM\TaskWorkerBundle\TaskWorkerInterface;
use VLM\TaskWorkerBundleDev\DevKernel;

require dirname(__DIR__) . '/vendor/autoload.php';
require 'DevKernel.php';

$kernel = new DevKernel('dev', true);
$kernel->boot();

$container = $kernel->getContainer();

try {
    $application = new Application('Task Worker Test', '1.0.0');

    /** @var TaskWorkerInterface $worker */
    $worker = $container->get(TaskWorkerInterface::class);
    /** @var Psr\Log\LoggerInterface $logger */
    $logger = $container->get('logger');

    $command = new RunCommand($worker, $logger);

    $application->add($command);
    $application->setDefaultCommand($command->getName(), true);

    $application->run();
} catch (Throwable $exception) {
    dd($exception);
}
