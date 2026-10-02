<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle;

use Doctrine\ORM\QueryBuilder;
use Freema\ReactAdminApiBundle\Request\ListDataRequest;
use Freema\ReactAdminApiBundle\Request\ListFieldPolicy;
use Freema\ReactAdminApiBundle\Result\ListDataResult;

/**
 * Trait to help implement the DataRepositoryListInterface.
 *
 * Filter keys and the sort field come from the client. They are accepted only
 * when they are configured here (custom filters, associations, sort map) or
 * are mapped entity fields the resource exposes, see ListFieldPolicy. Anything
 * else is refused with InvalidListRequestException (400).
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
            $sortDirection = ListFieldPolicy::sortDirection($dataRequest->getSortOrder());
            $requestedField = $dataRequest->getSortField();

            // Use sort field mapping if defined by repository
            $sortFieldMap = $this->getSortFieldMap();
            if (isset($sortFieldMap[$requestedField])) {
                $sortField = $sortFieldMap[$requestedField];
            } else {
                $this->createListFieldPolicy($dataRequest)->assertSortable($requestedField);
                $sortField = 'e.'.$requestedField;
            }
            $qb->orderBy($sortField, ListFieldPolicy::ormSortDirection($sortDirection));
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
        $policy = $this->createListFieldPolicy($dataRequest);
        $index = 0;

        // Apply field-specific filters
        foreach ($filterValues as $field => $value) {
            $field = (string) $field;
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

            // Parameter names are generated, never taken from the request.
            $param = 'filter_'.$index++;

            // Handle associations (e.g., threadId -> thread)
            if (isset($associations[$field])) {
                $associationConfig = $associations[$field];
                $associationField = $associationConfig['associationField'];

                if (is_array($value)) {
                    if (count($value) === 1) {
                        $qb->andWhere("e.$associationField = :$param")
                            ->setParameter($param, reset($value));
                    } else {
                        $qb->andWhere("e.$associationField IN (:$param)")
                            ->setParameter($param, $value);
                    }
                } else {
                    $qb->andWhere("e.$associationField = :$param")
                        ->setParameter($param, $value);
                }
                continue;
            }

            $policy->assertFilterable($field);

            // Handle array values (e.g., id IN [1, 2, 3])
            if (is_array($value)) {
                if (count($value) === 1) {
                    // Single value in array - use equals
                    $qb->andWhere("e.$field = :$param")
                        ->setParameter($param, reset($value));
                } else {
                    // Multiple values - use IN
                    $qb->andWhere("e.$field IN (:$param)")
                        ->setParameter($param, $value);
                }
            } else {
                // String value - use LIKE for string fields, equals for others
                // Check if field is numeric (id fields)
                if ($field === 'id' || str_ends_with($field, 'Id')) {
                    $qb->andWhere("e.$field = :$param")
                        ->setParameter($param, $value);
                } else {
                    $qb->andWhere("e.$field LIKE :$param")
                        ->setParameter($param, '%'.$value.'%');
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
     * Fields a client may filter on with a plain `field => value` filter.
     * Return null (the default) to allow the identifier plus every mapped
     * field the resource's DTO exposes as a public property. Custom filters
     * and association filters are always allowed.
     *
     * @return list<string>|null
     */
    protected function getFilterableFields(): ?array
    {
        return null;
    }

    /**
     * Fields a client may sort on, besides the keys of getSortFieldMap().
     * Return null (the default) for the same automatic rule as filters.
     *
     * @return list<string>|null
     */
    protected function getSortableFields(): ?array
    {
        return null;
    }

    protected function createListFieldPolicy(ListDataRequest $dataRequest): ListFieldPolicy
    {
        return new ListFieldPolicy(
            $this->getClassMetadata(),
            $dataRequest->getDtoClass(),
            $this->getFilterableFields(),
            $this->getSortableFields(),
        );
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

    /**
     * Get sort field mapping for DTO fields to entity fields.
     * This allows sorting by joined table fields or computed DTO properties.
     *
     * Example:
     * return [
     *     'regionName' => 'r.name',
     *     'campaignTitle' => 'c.title',
     * ];
     *
     * @return array<string, string>
     */
    protected function getSortFieldMap(): array
    {
        return [];
    }
}
