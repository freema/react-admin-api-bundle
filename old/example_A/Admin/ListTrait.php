<?php
declare(strict_types=1);

namespace Vlp\Mailer\Api\Admin;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Vlp\Mailer\Api\Admin\Request\ListDataRequest;
use Vlp\Mailer\Api\Admin\Result\ListDataResult;

trait ListTrait
{
    protected function getFullSearchFields(): array
    {
        return [];
    }

    protected function getAssociationsMap(): array
    {
        return [];
    }

    protected function getCustomFilters(): array
    {
        return [];
    }

    public function list(ListDataRequest $dataRequest): ListDataResult
    {
        return $dataRequest->createResult(
            $this->getListData($dataRequest),
            $this->getTotalCount($dataRequest)
        );
    }

    public function getListData(ListDataRequest $dataRequest): array
    {
        $this->checkQueryBuilderMethod();
        $criteria = $this->buildCriteria($dataRequest);
        $resultEntities = $this->matching($criteria)->toArray();

        return array_map(
            function ($entity) {
                return $this::mapToDto($entity);
            },
            $resultEntities
        );
    }

    public function getTotalCount(ListDataRequest $dataRequest): int
    {
        $this->checkQueryBuilderMethod();
        $criteria = $this->buildCriteria($dataRequest);

        return $this->matching($criteria)->count();
    }

    private function buildCriteria(ListDataRequest $dataRequest): Criteria
    {
        $criteria = Criteria::create();
        $associations = $this->getAssociationsMap();
        $customFilters = $this->getCustomFilters();

        foreach ($dataRequest->getFilter() as $field => $value) {
            if (isset($customFilters[$field])) {
                $customFilters[$field]($criteria, $value);
                continue;
            }

            if ('q' === $field) {
                if (empty($this->getFullSearchFields())) {
                    continue;
                }
                $orExpressions = [];
                foreach ($this->getFullSearchFields() as $searchField) {
                    $orExpressions[] = Criteria::expr()->contains($searchField, $value);
                }
                $criteria->andWhere(new CompositeExpression(CompositeExpression::TYPE_OR, $orExpressions));
            } elseif (isset($associations[$field])) {
                $ref = $this->_em->getReference($associations[$field]['targetEntity'], $value);
                $criteria->andWhere(Criteria::expr()->eq($associations[$field]['associationField'], $ref));
            } elseif (str_ends_with($field, '__from')) {
                $criteria->andWhere(
                    Criteria::expr()->gte(
                        str_replace('__from', '', $field),
                        \DateTimeImmutable::createFromFormat('Y-m-d', $value)->setTime(0, 0, 0)
                    )
                );
            } elseif (str_ends_with($field, '__to')) {
                $criteria->andWhere(
                    Criteria::expr()->lte(
                        str_replace('__to', '', $field),
                        \DateTimeImmutable::createFromFormat('Y-m-d', $value)->setTime(23, 59, 59)
                    )
                );
            } elseif (is_array($value)) {
                $orExpressions = [];
                foreach ($value as $item) {
                    $orExpressions[] = Criteria::expr()->eq($field, $item);
                }
                $criteria->andWhere(new CompositeExpression(CompositeExpression::TYPE_OR, $orExpressions));
            } else {
                $criteria->andWhere(Criteria::expr()->eq($field, $value));
            }
        }

        if ($dataRequest->getSortBy() && $dataRequest->getSortOrder()) {
            $criteria->orderBy([$dataRequest->getSortBy() => $dataRequest->getSortOrder()]);
        }

        if (is_numeric($dataRequest->getRangeTo()) && is_numeric($dataRequest->getRangeFrom())) {
            $criteria->setMaxResults($dataRequest->getRangeTo() - $dataRequest->getRangeFrom() + 1)
                ->setFirstResult($dataRequest->getRangeFrom());
        }

        return $criteria;
    }

    private function checkQueryBuilderMethod(): void
    {
        if (!method_exists($this, 'createQueryBuilder')) {
            throw new \LogicException(sprintf('The method "createQueryBuilder" does not exist in "%s".', get_class($this)));
        }
    }

    abstract protected function mapToDto($entity);
}
