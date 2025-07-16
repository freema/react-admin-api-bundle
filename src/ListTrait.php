<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle;

use Doctrine\ORM\QueryBuilder;
use Freema\ReactAdminApiBundle\Request\ListDataRequest;
use Freema\ReactAdminApiBundle\Result\ListDataResult;

/**
 * Trait to help implement the DataRepositoryListInterface.
 */
trait ListTrait
{
    /**
     * List entities with pagination, sorting and filtering.
     */
    public function list(ListDataRequest $dataRequest): ListDataResult
    {
        $qb = $this->createQueryBuilder('e');
        $this->applyFilters($qb, $dataRequest);
        
        // Count total
        $countQb = clone $qb;
        $countQb->select('COUNT(e.id)');
        $total = (int) $countQb->getQuery()->getSingleScalarResult();
        
        // Apply sorting
        if ($dataRequest->getSortField()) {
            $sortDirection = $dataRequest->getSortOrder() === 'DESC' ? 'DESC' : 'ASC';
            $qb->orderBy('e.' . $dataRequest->getSortField(), $sortDirection);
        }
        
        // Apply pagination
        if ($dataRequest->getOffset() !== null && $dataRequest->getLimit() !== null) {
            $qb->setFirstResult($dataRequest->getOffset());
            $qb->setMaxResults($dataRequest->getLimit());
        }
        
        $entities = $qb->getQuery()->getResult();
        
        $dtos = [];
        foreach ($entities as $entity) {
            $dtos[] = static::mapToDto($entity);
        }
        
        return new ListDataResult($dtos, $total);
    }
    
    /**
     * Apply filters from the request to the query builder.
     */
    protected function applyFilters(QueryBuilder $qb, ListDataRequest $dataRequest): void
    {
        $filterValues = $dataRequest->getFilterValues();
        
        // Apply field-specific filters
        foreach ($filterValues as $field => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            
            // Skip 'q' parameter as it's handled separately for full-text search
            if ($field === 'q') {
                continue;
            }
            
            $qb->andWhere("e.$field LIKE :$field")
                ->setParameter($field, '%' . $value . '%');
        }
        
        // Apply general filter (q parameter)
        if (isset($filterValues['q']) && $filterValues['q']) {
            $searchableFields = $this->getFullSearchFields();
            if (!empty($searchableFields)) {
                $conditions = [];
                foreach ($searchableFields as $field) {
                    $conditions[] = "e.$field LIKE :query";
                }
                $qb->andWhere('(' . implode(' OR ', $conditions) . ')')
                    ->setParameter('query', '%' . $filterValues['q'] . '%');
            }
        }
    }
    
    /**
     * Get the fields that should be searched when a general search query is provided.
     *
     * @return array<string>
     */
    abstract public function getFullSearchFields(): array;
}