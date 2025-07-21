<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Request;

use Freema\ReactAdminApiBundle\Request\Provider\List\ListDataRequestProviderManager;
use Symfony\Component\HttpFoundation\Request;

class ListDataRequestFactory
{
    public function __construct(
        private readonly ListDataRequestProviderManager $providerManager,
    ) {
    }

    public function createFromRequest(Request $request): ListDataRequest
    {
        return $this->providerManager->createRequestFromHttpRequest($request);
    }
}
