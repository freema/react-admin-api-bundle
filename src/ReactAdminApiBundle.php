<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle;

use Freema\ReactAdminApiBundle\DependencyInjection\Compiler\ListDataRequestProviderPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ReactAdminApiBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new ListDataRequestProviderPass());
    }

    public function getPath(): string
    {
        return __DIR__;
    }
}
