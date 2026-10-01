<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Tests\Functional\App;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Freema\ReactAdminApiBundle\ReactAdminApiBundle;
use Freema\ReactAdminApiBundle\Tests\Functional\App\Dto\AccountDto;
use Freema\ReactAdminApiBundle\Tests\Functional\App\Dto\NoteDto;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

class TestKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        return [new FrameworkBundle(), new DoctrineBundle(), new ReactAdminApiBundle()];
    }

    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $container->loadFromExtension('framework', [
            'test' => true,
            'secret' => 'test',
            'http_method_override' => false,
            'router' => ['utf8' => true],
        ]);
        $container->loadFromExtension('doctrine', [
            'dbal' => ['url' => 'sqlite:///:memory:'],
            // ORM 3.4+ on PHP 8.4 uses native lazy objects instead of var-exporter ghosts.
            'orm' => (\PHP_VERSION_ID >= 80400 ? ['enable_native_lazy_objects' => true] : []) + [
                'mappings' => [
                    'Test' => [
                        'is_bundle' => false,
                        'type' => 'attribute',
                        'dir' => __DIR__.'/Entity',
                        'prefix' => 'Freema\ReactAdminApiBundle\Tests\Functional\App\Entity',
                    ],
                ],
            ],
        ]);
        $container->loadFromExtension('react_admin_api', [
            'exception_listener' => ['enabled' => true, 'debug_mode' => false],
            'resources' => [
                'accounts' => ['dto_class' => AccountDto::class],
                'notes' => ['dto_class' => NoteDto::class],
            ],
        ]);

        $container->register('logger', NullLogger::class);
        $container->register(AccessListener::class)->setAutoconfigured(true);
        $container->register(Repository\AccountRepository::class)->setAutowired(true)->addTag('doctrine.repository_service');
        $container->register(Repository\NoteRepository::class)->setAutowired(true)->addTag('doctrine.repository_service');
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import('@ReactAdminApiBundle/Resources/config/routes.yaml');
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir().'/react-admin-api-bundle-tests/cache';
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir().'/react-admin-api-bundle-tests/log';
    }

    public function getProjectDir(): string
    {
        return __DIR__;
    }
}
