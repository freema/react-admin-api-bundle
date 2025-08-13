<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Event\List;

use Freema\ReactAdminApiBundle\Event\ReactAdminApiEvent;
use Freema\ReactAdminApiBundle\Request\ListDataRequest;
use Symfony\Component\HttpFoundation\Request;

/**
 * Event dispatched before data is loaded for list operations
 * Allows modification of filters, sorting, and pagination
 */
class PreListEvent extends ReactAdminApiEvent
{
    public function __construct(
        string $resource,
        Request $request,
        private ListDataRequest $listDataRequest,
    ) {
        parent::__construct($resource, $request);
    }

    /**
     * Get the list data request
     */
    public function getListDataRequest(): ListDataRequest
    {
        return $this->listDataRequest;
    }

    /**
     * Set the list data request
     */
    public function setListDataRequest(ListDataRequest $listDataRequest): self
    {
        $this->listDataRequest = $listDataRequest;

        return $this;
    }

    /**
     * Get current filters
     */
    public function getFilters(): array
    {
        return $this->listDataRequest->getFilterValues();
    }

    /**
     * Add a filter
     */
    public function addFilter(string $field, mixed $value): self
    {
        $filters = $this->listDataRequest->getFilterValues();
        $filters[$field] = $value;

        // Create new request with updated filters
        $filterJson = count($filters) > 0 ? json_encode($filters) : null;
        if ($filterJson === false) {
            $filterJson = null;
        }
        $this->listDataRequest = new ListDataRequest(
            limit: $this->listDataRequest->getLimit(),
            offset: $this->listDataRequest->getOffset(),
            sortField: $this->listDataRequest->getSortField(),
            sortOrder: $this->listDataRequest->getSortOrder(),
            filter: $filterJson,
            filterValues: $filters
        );

        return $this;
    }

    /**
     * Remove a filter
     */
    public function removeFilter(string $field): self
    {
        $filters = $this->listDataRequest->getFilterValues();
        unset($filters[$field]);

        // Create new request with updated filters
        $filterJson = count($filters) > 0 ? json_encode($filters) : null;
        if ($filterJson === false) {
            $filterJson = null;
        }
        $this->listDataRequest = new ListDataRequest(
            limit: $this->listDataRequest->getLimit(),
            offset: $this->listDataRequest->getOffset(),
            sortField: $this->listDataRequest->getSortField(),
            sortOrder: $this->listDataRequest->getSortOrder(),
            filter: $filterJson,
            filterValues: $filters
        );

        return $this;
    }

    /**
     * Set all filters
     */
    public function setFilters(array $filters): self
    {
        $filterJson = count($filters) > 0 ? json_encode($filters) : null;
        if ($filterJson === false) {
            $filterJson = null;
        }
        $this->listDataRequest = new ListDataRequest(
            limit: $this->listDataRequest->getLimit(),
            offset: $this->listDataRequest->getOffset(),
            sortField: $this->listDataRequest->getSortField(),
            sortOrder: $this->listDataRequest->getSortOrder(),
            filter: $filterJson,
            filterValues: $filters
        );

        return $this;
    }

    /**
     * Get current sorting
     */
    public function getSort(): array
    {
        return [
            'field' => $this->listDataRequest->getSortField(),
            'order' => $this->listDataRequest->getSortOrder(),
        ];
    }

    /**
     * Set sorting
     */
    public function setSort(?string $field, ?string $order = 'ASC'): self
    {
        $filters = $this->listDataRequest->getFilterValues();
        $filterJson = count($filters) > 0 ? json_encode($filters) : null;
        if ($filterJson === false) {
            $filterJson = null;
        }
        $this->listDataRequest = new ListDataRequest(
            limit: $this->listDataRequest->getLimit(),
            offset: $this->listDataRequest->getOffset(),
            sortField: $field,
            sortOrder: $order,
            filter: $filterJson,
            filterValues: $filters
        );

        return $this;
    }

    /**
     * Get current pagination
     */
    public function getPagination(): array
    {
        $offset = $this->listDataRequest->getOffset() ?? 0;
        $limit = $this->listDataRequest->getLimit() ?? 10;
        $page = $limit > 0 ? (int) floor($offset / $limit) + 1 : 1;
        
        return [
            'page' => $page,
            'perPage' => $limit,
            'offset' => $offset,
            'limit' => $limit,
        ];
    }

    /**
     * Set pagination
     */
    public function setPagination(?int $page, ?int $perPage): self
    {
        // Convert page/perPage to offset/limit
        $offset = $page && $perPage ? ($page - 1) * $perPage : null;
        $limit = $perPage;
        
        $filters = $this->listDataRequest->getFilterValues();
        $filterJson = count($filters) > 0 ? json_encode($filters) : null;
        if ($filterJson === false) {
            $filterJson = null;
        }
        $this->listDataRequest = new ListDataRequest(
            limit: $limit,
            offset: $offset,
            sortField: $this->listDataRequest->getSortField(),
            sortOrder: $this->listDataRequest->getSortOrder(),
            filter: $filterJson,
            filterValues: $filters
        );

        return $this;
    }
}
