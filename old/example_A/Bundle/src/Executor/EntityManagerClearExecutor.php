<?php

declare(strict_types=1);

namespace VLM\TaskWorkerBundle\Executor;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Throwable;
use VLM\TaskWorkerBundle\Exception\EntityManagerException;

class EntityManagerClearExecutor extends Executor
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    protected function before(): void
    {
        $this->entityManager->clear();
        $this->logger->debug('Entity manager cleared before task execution');
    }

    protected function after(): void
    {
        try {
            $this->entityManager->flush();
            $this->entityManager->clear();
            $this->logger->debug('Entity manager flushed and cleared after task execution');
        } catch (Throwable $e) {
            $this->logger->error('Error during entity manager flush', [
                'exception' => $e,
            ]);

            throw new EntityManagerException('Failed to flush entity manager: ' . $e->getMessage(), 0, $e);
        }
    }

    protected function onException(Throwable $t): void
    {
        if ($this->entityManager->getConnection()->isTransactionActive()) {
            $this->entityManager->rollback();
            $this->logger->debug('Transaction rolled back due to exception');
        }

        $this->entityManager->close();
        $this->logger->debug('Entity manager closed after exception');
    }
}
