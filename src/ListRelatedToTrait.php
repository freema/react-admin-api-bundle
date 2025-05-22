<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle;

use Doctrine\ORM\QueryBuilder;
use Freema\ReactAdminApiBundle\Interface\RelatedEntityInterface;
use Freema\ReactAdminApiBundle\Request\ListDataRequest;
use Freema\ReactAdminApiBundle\Result\ListDataResult;

/**
 * Trait to help implement the RelatedDataRepositoryListInterface.
 */
trait ListRelatedToTrait
{
    /**
     * List entities related to the provided entity with pagination, sorting and filtering.
     */
    public function listRelatedTo(ListDataRequest $dataRequest, RelatedEntityInterface $entity): ListDataResult
    {
        $qb = $this->createQueryBuilder('e');
        $this->applyRelationFilter($qb, $entity);
        // Check if the method has been aliased, and if so use the alias
        if (method_exists($this, 'applyRelatedFilters')) {
            $this->applyRelatedFilters($qb, $dataRequest);
        } else {
            $this->applyFilters($qb, $dataRequest);
        }
        
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
        if ($dataRequest->getPage() !== null && $dataRequest->getPerPage() !== null) {
            $qb->setFirstResult(($dataRequest->getPage() - 1) * $dataRequest->getPerPage());
            $qb->setMaxResults($dataRequest->getPerPage());
        }
        
        $entities = $qb->getQuery()->getResult();
        
        $dtos = [];
        foreach ($entities as $relatedEntity) {
            $dtos[] = static::mapToDto($relatedEntity);
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
     * Apply filters to limit results to those related to the given entity.
     */
    abstract protected function applyRelationFilter(QueryBuilder $qb, RelatedEntityInterface $entity): void;
    
    /**
     * Get the fields that should be searched when a general search query is provided.
     *
     * @return array<string>
     */
    abstract public function getFullSearchFields(): array;
}