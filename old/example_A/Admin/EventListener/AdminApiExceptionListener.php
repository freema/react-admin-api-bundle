<?php
declare(strict_types=1);

namespace Vlp\Mailer\Api\Admin\EventListener;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class AdminApiExceptionListener implements EventSubscriberInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct()
    {
        $this->logger = new NullLogger();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 50],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();
        $routeName = $request->attributes->get('_route');

        if (!$routeName || !str_starts_with($routeName, 'admin_api_resource')) {
            return;
        }

        $exception = $event->getThrowable();

        if ($exception instanceof \InvalidArgumentException) {
            $this->logger->warning(sprintf('Admin API Invalid argument: %s', $exception->getMessage()));
            $response = new JsonResponse([
                'error' => $exception->getMessage(),
            ], Response::HTTP_BAD_REQUEST);

            $event->setResponse($response);
        } elseif ($exception instanceof \LogicException) {
            $this->logger->error(sprintf('Admin API Logic exception: %s', $exception->getMessage()));
            $response = new JsonResponse([
                'error' => $exception->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);

            $event->setResponse($response);
        }
    }
}
