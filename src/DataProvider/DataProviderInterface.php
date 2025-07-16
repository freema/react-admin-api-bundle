<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\DataProvider;

use Freema\ReactAdminApiBundle\Request\ListDataRequest;
use Symfony\Component\HttpFoundation\Request;

/**
 * Interface for data providers that transform React Admin requests to bundle format
 */
interface DataProviderInterface
{
    /**
     * Check if this provider can handle the given request
     */
    public function supports(Request $request): bool;

    /**
     * Transform request parameters to ListDataRequest format
     */
    public function transformListRequest(Request $request): ListDataRequest;

    /**
     * Transform response data to provider-specific format
     */
    public function transformResponse(array $data, int $total): array;

    /**
     * Get the provider type identifier
     */
    public function getType(): string;
}