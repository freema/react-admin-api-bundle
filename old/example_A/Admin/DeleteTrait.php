<?php
declare(strict_types=1);

namespace Vlp\Mailer\Api\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Vlp\Mailer\Api\Admin\Request\DeleteDataRequest;
use Vlp\Mailer\Api\Admin\Request\DeleteManyDataRequest;
use Vlp\Mailer\Api\Admin\Result\DeleteDataResult;

trait DeleteTrait
{
    public function delete(DeleteDataRequest $dataRequest): DeleteDataResult
    {
        $entityManager = $this->getEntityManager();
        if (false === ($entityManager instanceof EntityManagerInterface)) {
            throw new \LogicException();
        }

        $entity = $this->find($dataRequest->getId());
        if (is_null($entity)) {
            return $dataRequest->createResult(
                status: false,
                errorMessages: ['Entity not found']
            );
        }

        $entityManager->remove($entity);
        $entityManager->flush();

        return $dataRequest->createResult(true);
    }

    public function deleteMany(DeleteManyDataRequest $dataRequest): DeleteDataResult
    {
        $entityManager = $this->getEntityManager();
        if (false === ($entityManager instanceof EntityManagerInterface)) {
            throw new \LogicException();
        }

        $entities = $this->findBy(['id' => $dataRequest->getIds()]);

        if (empty($entities)) {
            return $dataRequest->createResult(
                status: false,
                errorMessages: ['No entities deleted']
            );
        }

        foreach ($entities as $entity) {
            $entityManager->remove($entity);
        }
        $entityManager->flush();

        return $dataRequest->createResult(true);
    }
}
