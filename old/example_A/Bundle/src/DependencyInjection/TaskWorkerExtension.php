<?php

declare(strict_types=1);

namespace VLM\TaskWorkerBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Throwable;
use VLM\TaskWorkerBundle\Exception\TaskConfigurationException;

class TaskWorkerExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        try {
            $configuration = $this->getConfiguration($configs, $container);
            $config = $this->processConfiguration($configuration, $configs);

            $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
            $loader->load('services.yaml');

            if (isset($config['tasks']) && !empty($config['tasks'])) {
                $this->processTasks($config['tasks'], $container);
            }
        } catch (Throwable $e) {
            throw new TaskConfigurationException('Error loading task worker configuration: ' . $e->getMessage(), 0, $e);
        }
    }

    private function processTasks(array $tasks, ContainerBuilder $container): void
    {
        foreach ($tasks as $taskId => $taskConfig) {
            $taskDefinition = $container->register('vlm_task_worker.task.' . $taskId, $taskConfig['class']);
            $taskDefinition->addTag('taskWorker.task');

            // Add required arguments
            $taskDefinition->setArgument('$schedule', $taskConfig['schedule']);
            $taskDefinition->setArgument('$logger', new Reference('monolog.logger'));

            // Add optional arguments if defined
            if (isset($taskConfig['usersCount'])) {
                $taskDefinition->setArgument('$usersCount', $taskConfig['usersCount']);
            }

            if (isset($taskConfig['limit'])) {
                $taskDefinition->setArgument('$limit', $taskConfig['limit']);
            }

            if (isset($taskConfig['batchSize'])) {
                $taskDefinition->setArgument('$batchSize', $taskConfig['batchSize']);
            }
        }
    }

    public function getAlias(): string
    {
        return 'vlm_task_worker';
    }
}
