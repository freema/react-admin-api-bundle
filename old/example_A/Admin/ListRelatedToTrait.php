<?php

declare(strict_types=1);

namespace Vlp\Mailer\Api\Admin;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\ORM\NoResultException;
use Vlp\Mailer\Api\Admin\Interface\RelatedEntityInterface;
use Vlp\Mailer\Api\Admin\Request\ListDataRequest;
use Vlp\Mailer\Api\Admin\Result\ListDataResult;

trait ListRelatedToTrait
{
    public function listRelatedTo(ListDataRequest $dataRequest, RelatedEntityInterface $entity): ListDataResult
    {
        return $dataRequest->createResult($this->getRelatedListData($dataRequest, $entity), $this->getRelatedTotalCount($entity));
    }

    public function getRelatedListData(ListDataRequest $dataRequest, RelatedEntityInterface $entity): array
    {
        $this->checkQueryBuilderMethod();

        $criteria = Criteria::create();

        $criteria->andWhere(Criteria::expr()->eq($entity->getAlias(), $entity));
        foreach ($dataRequest->getFilter() as $field => $value) {
            if ('q' === $field) {
                $orExpressions = [];
                foreach ($this->getFullSearchFields() as $field) {
                    $orExpressions[] = Criteria::expr()->contains($field, $value);
                }
                $criteria->andWhere(new CompositeExpression(CompositeExpression::TYPE_OR, $orExpressions));
            } else {
                if (is_array($value)) {
                    $orExpressions = [];
                    foreach ($value as $item) {
                        $orExpressions[] = Criteria::expr()->eq($field, $item);
                    }
                    $criteria->andWhere(new CompositeExpression(CompositeExpression::TYPE_OR, $orExpressions));
                } else {
                    $criteria->andWhere(Criteria::expr()->eq($field, $value));
                }
            }
        }

        if ($dataRequest->getSortBy() && $dataRequest->getSortOrder()) {
            $criteria->orderBy([$dataRequest->getSortBy() => $dataRequest->getSortOrder()]);
        }

        if (is_numeric($dataRequest->getRangeTo()) && is_numeric($dataRequest->getRangeFrom())) {
            $criteria->setMaxResults($dataRequest->getRangeTo() - $dataRequest->getRangeFrom() + 1)
                ->setFirstResult($dataRequest->getRangeFrom());
        }

        $resultEntities = $this->matching($criteria)->toArray();

        return array_map(
            function ($entity) {
                return $this::mapToDto($entity);
            },
            $resultEntities
        );
    }

    public function getRelatedTotalCount(RelatedEntityInterface $entity): int
    {
        $this->checkQueryBuilderMethod();

        try {
            return (int) $this->createQueryBuilder('a')
                ->select('count(a.id)')
                ->andWhere(sprintf('a.%s = :entity', $entity->getAlias()))
                ->setParameter('entity', $entity)
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NoResultException $e) {
            return 0;
        }
    }

    private function checkQueryBuilderMethod(): void
    {
        if (!method_exists($this, 'createQueryBuilder')) {
            throw new \LogicException(sprintf('The method "createQueryBuilder" does not exist in "%s".', get_class($this)));
        }
    }

    abstract protected function mapToDto($entity);
}
