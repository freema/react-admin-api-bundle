<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle;

use Doctrine\ORM\QueryBuilder;
use Freema\ReactAdminApiBundle\Interface\RelatedEntityInterface;
use Freema\ReactAdminApiBundle\Request\ListDataRequest;
use Freema\ReactAdminApiBundle\Request\ListFieldPolicy;
use Freema\ReactAdminApiBundle\Result\ListDataResult;

/**
 * Trait to help implement the RelatedDataRepositoryListInterface.
 *
 * Filter keys and the sort field are checked like in ListTrait (see
 * ListFieldPolicy). The trait reads getFilterableFields() and
 * getSortableFields() when the repository defines them, without declaring
 * them itself, so it can still be combined with ListTrait.
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
            $sortDirection = ListFieldPolicy::sortDirection($dataRequest->getSortOrder());
            $this->createRelatedListFieldPolicy($dataRequest)->assertSortable($dataRequest->getSortField());
            $qb->orderBy('e.'.$dataRequest->getSortField(), $sortDirection);
        }

        // Apply pagination
        if ($dataRequest->getOffset() !== null && $dataRequest->getLimit() !== null) {
            $qb->setFirstResult($dataRequest->getOffset());
            $qb->setMaxResults($dataRequest->getLimit());
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
        $policy = $this->createRelatedListFieldPolicy($dataRequest);
        $index = 0;

        // Apply field-specific filters
        foreach ($filterValues as $field => $value) {
            $field = (string) $field;
            if ($value === null || $value === '' || $field === 'q') {
                continue;
            }

            $policy->assertFilterable($field);
            $param = 'filter_'.$index++;
            $qb->andWhere("e.$field LIKE :$param")
                ->setParameter($param, '%'.(is_scalar($value) ? $value : '').'%');
        }

        // Apply general filter (q parameter)
        if (isset($filterValues['q']) && $filterValues['q']) {
            $searchableFields = $this->getFullSearchFields();
            if (!empty($searchableFields)) {
                $conditions = [];
                foreach ($searchableFields as $field) {
                    $conditions[] = "e.$field LIKE :query";
                }
                $qb->andWhere('('.implode(' OR ', $conditions).')')
                    ->setParameter('query', '%'.$filterValues['q'].'%');
            }
        }
    }

    private function createRelatedListFieldPolicy(ListDataRequest $dataRequest): ListFieldPolicy
    {
        return new ListFieldPolicy(
            $this->getClassMetadata(),
            $dataRequest->getDtoClass(),
            method_exists($this, 'getFilterableFields') ? $this->getFilterableFields() : null,
            method_exists($this, 'getSortableFields') ? $this->getSortableFields() : null,
        );
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
