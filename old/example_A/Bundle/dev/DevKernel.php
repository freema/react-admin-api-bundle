<?php

declare(strict_types=1);

namespace VLM\TaskWorkerBundleDev;

use Symfony\Bundle\DebugBundle\DebugBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use VLM\TaskWorkerBundle\TaskWorkerBundle;

class DevKernel extends Kernel
{
    public function registerBundles(): iterable
    {
        return [
            new DebugBundle(),
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
                        'path' => '%kernel.logs_dir%/dev.log',
                        'level' => 'debug',
                    ],
                    'console' => [
                        'type' => 'console',
                        'process_psr_3_messages' => false,
                        'channels' => ['!event', '!doctrine', '!console'],
                    ],
                ],
            ]);

            $container->loadFromExtension('vlm_task_worker');
        });

        $loader->load(__DIR__ . '/config/services.yaml');

        $loader->load(function (ContainerBuilder $container): void {
            $container->loadFromExtension('vlm_task_worker', [
                'tasks' => [
                    'null_task' => [
                        'class' => NullTask::class,
                        'schedule' => '*/5 * * * *',
                    ],
                ],
            ]);
        });
    }

    public function getCacheDir(): string
    {
        if (method_exists($this, 'getProjectDir')) {
            return $this->getProjectDir() . '/dev/cache/' . $this->getEnvironment();
        }

        return parent::getCacheDir();
    }

    public function getLogDir(): string
    {
        if (method_exists($this, 'getProjectDir')) {
            return $this->getProjectDir() . '/dev/cache/' . $this->getEnvironment();
        }

        return parent::getLogDir();
    }
}
