<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Request\Provider;

use Symfony\Component\HttpFoundation\Request;

interface ProviderInterface
{
    /**
     * Check if this provider can handle the given request
     */
    public function supports(Request $request): bool;

    /**
     * Get the priority of this provider (higher = checked first)
     */
    public function getPriority(): int;

    /**
     * Get provider name for identification
     */
    public function getName(): string;
}