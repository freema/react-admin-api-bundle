<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Event\Common;

use Freema\ReactAdminApiBundle\Event\ReactAdminApiEvent;
use Symfony\Component\HttpFoundation\Request;

/**
 * Event dispatched on every resource access
 * Useful for logging, audit trails, and access control
 */
class ResourceAccessEvent extends ReactAdminApiEvent
{
    public function __construct(
        string $resource,
        Request $request,
        private readonly string $operation,
        private readonly ?string $resourceId = null,
    ) {
        parent::__construct($resource, $request);
    }

    /**
     * Get the operation being performed (list, get, create, update, delete)
     */
    public function getOperation(): string
    {
        return $this->operation;
    }

    /**
     * Get the resource ID (if applicable)
     */
    public function getResourceId(): ?string
    {
        return $this->resourceId;
    }

    /**
     * Check if this is a read operation
     */
    public function isReadOperation(): bool
    {
        return in_array($this->operation, ['list', 'get'], true);
    }

    /**
     * Check if this is a write operation
     */
    public function isWriteOperation(): bool
    {
        return in_array($this->operation, ['create', 'update', 'delete'], true);
    }

    /**
     * Check if this is a bulk operation
     */
    public function isBulkOperation(): bool
    {
        return str_contains($this->operation, 'Many') || $this->operation === 'list';
    }

    /**
     * Get access information
     */
    public function getAccessInfo(): array
    {
        return [
            'resource' => $this->getResource(),
            'operation' => $this->operation,
            'resourceId' => $this->resourceId,
            'method' => $this->getMethod(),
            'route' => $this->getRouteName(),
            'ip' => $this->getClientIp(),
            'userAgent' => $this->getUserAgent(),
            'timestamp' => new \DateTimeImmutable(),
            'isRead' => $this->isReadOperation(),
            'isWrite' => $this->isWriteOperation(),
            'isBulk' => $this->isBulkOperation(),
        ];
    }

    /**
     * Get request parameters
     */
    public function getRequestParameters(): array
    {
        return [
            'query' => $this->getRequest()->query->all(),
            'headers' => $this->getRequest()->headers->all(),
            'content' => $this->getRequest()->getContent(),
        ];
    }
}
