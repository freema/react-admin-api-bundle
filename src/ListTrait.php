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
        $countField = $this->getCountField();
        $countQb->select("COUNT(e.$countField)");
        $total = (int) $countQb->getQuery()->getSingleScalarResult();

        // Apply sorting
        if ($dataRequest->getSortField()) {
            $sortDirection = $dataRequest->getSortOrder() === 'DESC' ? 'DESC' : 'ASC';
            $qb->orderBy('e.'.$dataRequest->getSortField(), $sortDirection);
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
        $associations = $this->getAssociationsMap();
        $customFilters = $this->getCustomFilters();

        // Apply field-specific filters
        foreach ($filterValues as $field => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            // Skip 'q' parameter as it's handled separately for full-text search
            if ($field === 'q') {
                continue;
            }

            // Handle custom filters (e.g., hasParent)
            if (isset($customFilters[$field])) {
                $customFilters[$field]($qb, $value);
                continue;
            }

            // Handle associations (e.g., threadId -> thread)
            if (isset($associations[$field])) {
                $associationConfig = $associations[$field];
                $associationField = $associationConfig['associationField'];

                if (is_array($value)) {
                    if (count($value) === 1) {
                        $qb->andWhere("e.$associationField = :$field")
                            ->setParameter($field, $value[0]);
                    } else {
                        $qb->andWhere("e.$associationField IN (:$field)")
                            ->setParameter($field, $value);
                    }
                } else {
                    $qb->andWhere("e.$associationField = :$field")
                        ->setParameter($field, $value);
                }
                continue;
            }

            // Handle array values (e.g., id IN [1, 2, 3])
            if (is_array($value)) {
                if (count($value) === 1) {
                    // Single value in array - use equals
                    $qb->andWhere("e.$field = :$field")
                        ->setParameter($field, $value[0]);
                } else {
                    // Multiple values - use IN
                    $qb->andWhere("e.$field IN (:$field)")
                        ->setParameter($field, $value);
                }
            } else {
                // String value - use LIKE for string fields, equals for others
                // Check if field is numeric (id fields)
                if ($field === 'id' || str_ends_with($field, 'Id')) {
                    $qb->andWhere("e.$field = :$field")
                        ->setParameter($field, $value);
                } else {
                    $qb->andWhere("e.$field LIKE :$field")
                        ->setParameter($field, '%'.$value.'%');
                }
            }
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

    /**
     * Get the fields that should be searched when a general search query is provided.
     *
     * @return array<string>
     */
    abstract public function getFullSearchFields(): array;

    /**
     * Get the associations mapping for filters.
     * Maps filter fields to actual entity associations.
     *
     * Example:
     * return [
     *     'threadId' => [
     *         'associationField' => 'thread',
     *         'targetEntity' => Thread::class,
     *     ],
     * ];
     *
     * @return array<string, array{associationField: string, targetEntity: string}>
     */
    protected function getAssociationsMap(): array
    {
        return [];
    }

    /**
     * Get custom filter handlers for fields that need special processing.
     *
     * Example:
     * return [
     *     'hasParent' => function(QueryBuilder $qb, $value) {
     *         $hasParent = $value === 'true' || $value === true;
     *         if ($hasParent) {
     *             $qb->andWhere('e.parent IS NOT NULL');
     *         } else {
     *             $qb->andWhere('e.parent IS NULL');
     *         }
     *     },
     * ];
     *
     * @return array<string, callable>
     */
    protected function getCustomFilters(): array
    {
        return [];
    }

    /**
     * Get the field name to use for COUNT queries.
     * Default is 'id', but entities with different primary keys can override this.
     *
     * @return string
     */
    protected function getCountField(): string
    {
        return 'id';
    }
}
