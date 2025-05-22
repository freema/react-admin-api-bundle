<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Tests;

use Freema\ReactAdminApiBundle\Controller\ResourceController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class ResourceControllerTest extends TestCase
{
    public function test_controller_can_be_instantiated(): void
    {
        $controller = new ResourceController();
        
        $this->assertInstanceOf(ResourceController::class, $controller);
    }
    
    // Další testy budou přidány později
}