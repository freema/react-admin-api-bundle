<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Request\Provider\List;

use Freema\ReactAdminApiBundle\Request\ListDataRequest;
use Freema\ReactAdminApiBundle\Request\Provider\ProviderInterface;
use Symfony\Component\HttpFoundation\Request;

interface ListDataRequestProviderInterface extends ProviderInterface
{
    /**
     * Parse request and return standardized ListDataRequest object
     */
    public function createRequest(Request $request): ListDataRequest;
}