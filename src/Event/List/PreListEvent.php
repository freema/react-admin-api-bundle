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
        private ListDataRequest $listDataRequest
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
        $this->listDataRequest = new ListDataRequest(
            $this->listDataRequest->getPage(),
            $this->listDataRequest->getPerPage(),
            $this->listDataRequest->getSortField(),
            $this->listDataRequest->getSortOrder(),
            $filters
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
        $this->listDataRequest = new ListDataRequest(
            $this->listDataRequest->getPage(),
            $this->listDataRequest->getPerPage(),
            $this->listDataRequest->getSortField(),
            $this->listDataRequest->getSortOrder(),
            $filters
        );
        
        return $this;
    }

    /**
     * Set all filters
     */
    public function setFilters(array $filters): self
    {
        $this->listDataRequest = new ListDataRequest(
            $this->listDataRequest->getPage(),
            $this->listDataRequest->getPerPage(),
            $this->listDataRequest->getSortField(),
            $this->listDataRequest->getSortOrder(),
            $filters
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
            'order' => $this->listDataRequest->getSortOrder()
        ];
    }

    /**
     * Set sorting
     */
    public function setSort(?string $field, ?string $order = 'ASC'): self
    {
        $this->listDataRequest = new ListDataRequest(
            $this->listDataRequest->getPage(),
            $this->listDataRequest->getPerPage(),
            $field,
            $order,
            $this->listDataRequest->getFilterValues()
        );
        
        return $this;
    }

    /**
     * Get current pagination
     */
    public function getPagination(): array
    {
        return [
            'page' => $this->listDataRequest->getPage(),
            'perPage' => $this->listDataRequest->getPerPage(),
            'offset' => $this->listDataRequest->getOffset(),
            'limit' => $this->listDataRequest->getLimit()
        ];
    }

    /**
     * Set pagination
     */
    public function setPagination(?int $page, ?int $perPage): self
    {
        $this->listDataRequest = new ListDataRequest(
            $page,
            $perPage,
            $this->listDataRequest->getSortField(),
            $this->listDataRequest->getSortOrder(),
            $this->listDataRequest->getFilterValues()
        );
        
        return $this;
    }
}