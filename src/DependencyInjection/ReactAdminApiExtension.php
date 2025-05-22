<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Freema\ReactAdminApiBundle\Service\ResourceConfigurationService;

class ReactAdminApiExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');
        
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        
        // Create ResourceConfigurationService with resource configuration
        $resourceConfigServiceDefinition = new Definition(ResourceConfigurationService::class);
        $resourceConfigServiceDefinition->setArguments([$config['resources']]);
        $container->setDefinition(ResourceConfigurationService::class, $resourceConfigServiceDefinition);
    }
    
    public function getAlias(): string
    {
        return 'react_admin_api';
    }
}