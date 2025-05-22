<?php

declare(strict_types=1);

namespace VLM\TaskWorkerBundle\Tests;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use VLM\TaskWorkerBundle\TaskWorkerBundle;
use VLM\TaskWorkerBundle\Tests\Fixtures\TestTask;

class TestKernel extends Kernel
{
    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new MonologBundle(),
            new TaskWorkerBundle(),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(function (ContainerBuilder $container): void {
            $container->loadFromExtension('framework', [
                'test' => true,
                'secret' => 'test',
            ]);

            $container->loadFromExtension('monolog', [
                'handlers' => [
                    'main' => [
                        'type' => 'stream',
                        'path' => '%kernel.logs_dir%/test.log',
                        'level' => 'debug',
                    ],
                ],
            ]);

            $container->loadFromExtension('vlm_task_worker', [
                'tasks' => [
                    'test_task' => [
                        'class' => TestTask::class,
                        'schedule' => '* * * * *',
                    ],
                ],
            ]);
        });
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir() . '/cache/' . $this->environment;
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir() . '/logs/' . $this->environment;
    }
}
