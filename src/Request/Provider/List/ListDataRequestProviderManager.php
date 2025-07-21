<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Request\Provider\List;

use Freema\ReactAdminApiBundle\Request\ListDataRequest;
use Symfony\Component\HttpFoundation\Request;

class ListDataRequestProviderManager
{
    /**
     * @var ListDataRequestProviderInterface[]
     */
    private array $providers = [];

    public function addProvider(ListDataRequestProviderInterface $provider): void
    {
        $this->providers[] = $provider;
        // Sort by priority (higher priority first)
        usort($this->providers, fn ($a, $b) => $b->getPriority() <=> $a->getPriority());
    }

    public function createRequestFromHttpRequest(Request $request): ListDataRequest
    {
        foreach ($this->providers as $provider) {
            if ($provider->supports($request)) {
                return $provider->createRequest($request);
            }
        }

        throw new \RuntimeException('No provider found for the given request. Available providers: '.
            implode(', ', array_map(fn ($p) => $p->getName(), $this->providers)));
    }

    public function getProviders(): array
    {
        return $this->providers;
    }
}
