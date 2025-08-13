<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Event\List;

use Freema\ReactAdminApiBundle\Event\ReactAdminApiEvent;
use Freema\ReactAdminApiBundle\Request\ListDataRequest;
use Freema\ReactAdminApiBundle\Result\ListDataResult;
use Symfony\Component\HttpFoundation\Request;

/**
 * Event dispatched after data is loaded for list operations
 * Allows modification of results and additional data processing
 */
class PostListEvent extends ReactAdminApiEvent
{
    public function __construct(
        string $resource,
        Request $request,
        private readonly ListDataRequest $listDataRequest,
        private ListDataResult $listDataResult,
    ) {
        parent::__construct($resource, $request);
    }

    /**
     * Get the list data request that was used
     */
    public function getListDataRequest(): ListDataRequest
    {
        return $this->listDataRequest;
    }

    /**
     * Get the list data result
     */
    public function getListDataResult(): ListDataResult
    {
        return $this->listDataResult;
    }

    /**
     * Set the list data result
     */
    public function setListDataResult(ListDataResult $listDataResult): self
    {
        $this->listDataResult = $listDataResult;

        return $this;
    }

    /**
     * Get the data items
     */
    public function getData(): array
    {
        return $this->listDataResult->getData();
    }

    /**
     * Set the data items
     */
    public function setData(array $data): self
    {
        $this->listDataResult = new ListDataResult($data, $this->listDataResult->getTotal());

        return $this;
    }

    /**
     * Get the total count
     */
    public function getTotal(): int
    {
        return $this->listDataResult->getTotal();
    }

    /**
     * Set the total count
     */
    public function setTotal(int $total): self
    {
        $this->listDataResult = new ListDataResult($this->listDataResult->getData(), $total);

        return $this;
    }

    /**
     * Add an item to the result
     */
    public function addItem(mixed $item): self
    {
        $data = $this->listDataResult->getData();
        $data[] = $item;
        $this->listDataResult = new ListDataResult($data, $this->listDataResult->getTotal());

        return $this;
    }

    /**
     * Remove an item from the result by index
     */
    public function removeItem(int $index): self
    {
        $data = $this->listDataResult->getData();
        if (isset($data[$index])) {
            unset($data[$index]);
            $data = array_values($data); // Reindex array
            $this->listDataResult = new ListDataResult($data, $this->listDataResult->getTotal());
        }

        return $this;
    }

    /**
     * Filter items based on a callback
     */
    public function filterItems(callable $callback): self
    {
        $data = array_filter($this->listDataResult->getData(), $callback);
        $data = array_values($data); // Reindex array
        $this->listDataResult = new ListDataResult($data, count($data));

        return $this;
    }

    /**
     * Map items using a callback
     */
    public function mapItems(callable $callback): self
    {
        $data = array_map($callback, $this->listDataResult->getData());
        $this->listDataResult = new ListDataResult($data, $this->listDataResult->getTotal());

        return $this;
    }

    /**
     * Sort items using a callback
     */
    public function sortItems(callable $callback): self
    {
        $data = $this->listDataResult->getData();
        usort($data, $callback);
        $this->listDataResult = new ListDataResult($data, $this->listDataResult->getTotal());

        return $this;
    }

    /**
     * Get statistics about the result
     */
    public function getStatistics(): array
    {
        $offset = $this->listDataRequest->getOffset() ?? 0;
        $limit = $this->listDataRequest->getLimit() ?? 10;
        $page = $limit > 0 ? (int) floor($offset / $limit) + 1 : 1;
        
        return [
            'count' => count($this->listDataResult->getData()),
            'total' => $this->listDataResult->getTotal(),
            'page' => $page,
            'perPage' => $limit,
            'hasMore' => $this->listDataResult->getTotal() > ($offset + count($this->listDataResult->getData())),
        ];
    }
}
