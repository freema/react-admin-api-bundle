<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Tests;

use Freema\ReactAdminApiBundle\Controller\ResourceController;
use PHPUnit\Framework\TestCase;

class ResourceControllerTest extends TestCase
{
    public function test_controller_can_be_instantiated(): void
    {
        $resourceConfigurationService = $this->createMock(\Freema\ReactAdminApiBundle\Service\ResourceConfigurationService::class);
        $dataProviderFactory = $this->createMock(\Freema\ReactAdminApiBundle\DataProvider\DataProviderFactory::class);
        $dtoFactory = $this->createMock(\Freema\ReactAdminApiBundle\Service\DtoFactory::class);
        $eventDispatcher = $this->createMock(\Symfony\Contracts\EventDispatcher\EventDispatcherInterface::class);

        $controller = new ResourceController(
            $resourceConfigurationService,
            $dataProviderFactory,
            $dtoFactory,
            $eventDispatcher
        );

        $this->assertInstanceOf(ResourceController::class, $controller);
    }

    // Další testy budou přidány později
}
