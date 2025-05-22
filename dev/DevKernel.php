<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Dev;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Freema\ReactAdminApiBundle\ReactAdminApiBundle;

class DevKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new MonologBundle(),
            new DoctrineBundle(),
            new ReactAdminApiBundle(),
        ];
    }

    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $container->loadFromExtension('framework', [
            'test' => false,
            'router' => [
                'utf8' => true,
            ],
            'secret' => 'test',
        ]);

        $container->loadFromExtension('monolog', [
            'handlers' => [
                'main' => [
                    'type' => 'stream',
                    'path' => '%kernel.logs_dir%/dev.log',
                    'level' => 'debug',
                ],
            ],
        ]);


        $container->loadFromExtension('react_admin_api', [
            'exception_listener' => [
                'enabled' => false,
                'debug_mode' => false,
            ],
            'resources' => [
                'users' => [
                    'dto_class' => 'Freema\ReactAdminApiBundle\Dev\Dto\UserDto',
                ],
            ],
        ]);

        $loader->load(__DIR__.'/config/services.yaml');
        $loader->load(__DIR__.'/config/packages/*.yaml', 'glob');
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import(__DIR__.'/config/routes.yaml');
    }

    public function getProjectDir(): string
    {
        return __DIR__;
    }
}