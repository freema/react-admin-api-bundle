<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\DependencyInjection\Compiler;

use Freema\ReactAdminApiBundle\Request\Provider\List\ListDataRequestProviderManager;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ListDataRequestProviderPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(ListDataRequestProviderManager::class)) {
            return;
        }

        $definition = $container->findDefinition(ListDataRequestProviderManager::class);
        $taggedServices = $container->findTaggedServiceIds('react_admin_api.list_data_request_provider');

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('addProvider', [new Reference($id)]);
        }
    }
}