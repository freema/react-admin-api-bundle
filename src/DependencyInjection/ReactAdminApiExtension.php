<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\DependencyInjection;

use Freema\ReactAdminApiBundle\DataProvider\CustomDataProvider;
use Freema\ReactAdminApiBundle\DataProvider\DataProviderFactory;
use Freema\ReactAdminApiBundle\DataProvider\SimpleRestDataProvider;
use Freema\ReactAdminApiBundle\EventListener\ApiExceptionListener;
use Freema\ReactAdminApiBundle\Request\Provider\List\ListDataRequestProviderInterface;
use Freema\ReactAdminApiBundle\Service\ResourceConfigurationService;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

class ReactAdminApiExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yaml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // Create ResourceConfigurationService with resource configuration
        $resourceConfigServiceDefinition = new Definition(ResourceConfigurationService::class);
        $resourceConfigServiceDefinition->setArguments([$config['resources']]);
        $container->setDefinition(ResourceConfigurationService::class, $resourceConfigServiceDefinition);

        // Conditionally register ApiExceptionListener based on configuration
        if ($config['exception_listener']['enabled']) {
            $apiExceptionListenerDefinition = new Definition(ApiExceptionListener::class);
            $apiExceptionListenerDefinition->setArguments([
                new Reference('router'),
                $config['exception_listener']['enabled'],
                $config['exception_listener']['debug_mode'],
            ]);
            $apiExceptionListenerDefinition->addMethodCall('setLogger', [new Reference('logger')]);
            $apiExceptionListenerDefinition->addTag('kernel.event_subscriber');
            $container->setDefinition(ApiExceptionListener::class, $apiExceptionListenerDefinition);
        }

        // Register providers to the manager
        $container->registerForAutoconfiguration(ListDataRequestProviderInterface::class)
            ->addTag('react_admin_api.list_data_request_provider');

        // Register custom providers from configuration
        if (!empty($config['providers']['list_data_request'])) {
            foreach ($config['providers']['list_data_request'] as $name => $providerConfig) {
                $definition = new Definition($providerConfig['class']);
                $definition->addTag('react_admin_api.list_data_request_provider', ['priority' => $providerConfig['priority']]);
                $container->setDefinition('react_admin_api.provider.list_data_request.'.$name, $definition);
            }
        }

        // Register data providers
        $this->registerDataProviders($container, $config['data_provider']);
    }

    public function getAlias(): string
    {
        return 'react_admin_api';
    }

    private function registerDataProviders(ContainerBuilder $container, array $config): void
    {
        // Register individual data providers
        $customProviderDefinition = new Definition(CustomDataProvider::class);
        $container->setDefinition(CustomDataProvider::class, $customProviderDefinition);

        $simpleRestProviderDefinition = new Definition(SimpleRestDataProvider::class);
        $container->setDefinition(SimpleRestDataProvider::class, $simpleRestProviderDefinition);

        // Register data provider factory
        $factoryDefinition = new Definition(DataProviderFactory::class);
        $factoryDefinition->setArguments([
            [
                new Reference(SimpleRestDataProvider::class),
                new Reference(CustomDataProvider::class), // Custom provider as fallback
            ],
            $config['type'], // Default provider type
        ]);
        $container->setDefinition(DataProviderFactory::class, $factoryDefinition);
    }
}
