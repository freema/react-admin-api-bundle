<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Event;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Base class for all React Admin API events
 */
abstract class ReactAdminApiEvent extends Event
{
    private array $context = [];
    private bool $cancelled = false;

    public function __construct(
        private readonly string $resource,
        private readonly Request $request,
    ) {
    }

    public function getResource(): string
    {
        return $this->resource;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * Get event context data
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Set context data
     */
    public function setContext(array $context): self
    {
        $this->context = $context;

        return $this;
    }

    /**
     * Add context data
     */
    public function addContext(string $key, mixed $value): self
    {
        $this->context[$key] = $value;

        return $this;
    }

    /**
     * Get context value by key
     */
    public function getContextValue(string $key, mixed $default = null): mixed
    {
        return $this->context[$key] ?? $default;
    }

    /**
     * Check if event has been cancelled
     */
    public function isCancelled(): bool
    {
        return $this->cancelled;
    }

    /**
     * Cancel the event (prevents further processing)
     */
    public function cancel(): self
    {
        $this->cancelled = true;
        $this->stopPropagation();

        return $this;
    }

    /**
     * Get the route name for this request
     */
    public function getRouteName(): ?string
    {
        return $this->request->attributes->get('_route');
    }

    /**
     * Get the HTTP method for this request
     */
    public function getMethod(): string
    {
        return $this->request->getMethod();
    }

    /**
     * Get the user IP address
     */
    public function getClientIp(): ?string
    {
        return $this->request->getClientIp();
    }

    /**
     * Get the user agent
     */
    public function getUserAgent(): ?string
    {
        return $this->request->headers->get('User-Agent');
    }
}
