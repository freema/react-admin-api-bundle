<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\DataProvider;

use Symfony\Component\HttpFoundation\Request;

/**
 * Factory for creating appropriate data providers based on request
 */
class DataProviderFactory
{
    /**
     * @var DataProviderInterface[]
     */
    private array $providers = [];

    private string $defaultProvider;

    private ?string $forceProvider = null;

    /**
     * @param DataProviderInterface[] $providers
     */
    public function __construct(array $providers = [], string $defaultProvider = 'custom', ?string $forceProvider = null)
    {
        $this->providers = $providers;
        $this->defaultProvider = $defaultProvider;
        $this->forceProvider = $forceProvider;
    }

    /**
     * Add a data provider
     */
    public function addProvider(DataProviderInterface $provider): void
    {
        $this->providers[] = $provider;
    }

    /**
     * Get the appropriate data provider for the request
     */
    public function getProvider(Request $request): DataProviderInterface
    {
        // If force provider is set, use it instead of auto-detection
        if ($this->forceProvider !== null) {
            $forcedProvider = $this->getProviderByType($this->forceProvider);
            if ($forcedProvider !== null) {
                return $forcedProvider;
            }
        }

        // Try to find a provider that supports the request
        foreach ($this->providers as $provider) {
            if ($provider->supports($request)) {
                return $provider;
            }
        }

        // Fallback to default provider
        return $this->getDefaultProvider();
    }

    /**
     * Get provider by type
     */
    public function getProviderByType(string $type): ?DataProviderInterface
    {
        foreach ($this->providers as $provider) {
            if ($provider->getType() === $type) {
                return $provider;
            }
        }

        return null;
    }

    /**
     * Get default provider
     */
    private function getDefaultProvider(): DataProviderInterface
    {
        $defaultProvider = $this->getProviderByType($this->defaultProvider);

        if ($defaultProvider === null) {
            // Ultimate fallback - create custom provider
            return new CustomDataProvider();
        }

        return $defaultProvider;
    }

    /**
     * Get all registered providers
     *
     * @return DataProviderInterface[]
     */
    public function getProviders(): array
    {
        return $this->providers;
    }
}
