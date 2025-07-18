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
        $listDataRequestFactory = $this->createMock(\Freema\ReactAdminApiBundle\Request\ListDataRequestFactory::class);
        $dataProviderFactory = $this->createMock(\Freema\ReactAdminApiBundle\DataProvider\DataProviderFactory::class);
        $dtoFactory = $this->createMock(\Freema\ReactAdminApiBundle\Service\DtoFactory::class);

        $controller = new ResourceController(
            $resourceConfigurationService,
            $listDataRequestFactory,
            $dataProviderFactory,
            $dtoFactory
        );

        $this->assertInstanceOf(ResourceController::class, $controller);
    }

    // Další testy budou přidány později
}
