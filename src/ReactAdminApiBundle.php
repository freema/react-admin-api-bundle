<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ReactAdminApiBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
    }
    
    public function getPath(): string
    {
        return __DIR__;
    }
}