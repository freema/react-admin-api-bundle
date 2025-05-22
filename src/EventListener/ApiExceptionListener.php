<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\EventListener;

use Freema\ReactAdminApiBundle\Exception\EntityNotFoundException;
use Freema\ReactAdminApiBundle\Exception\ValidationException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ApiExceptionListener implements EventSubscriberInterface
{
    public function __construct(private ?LoggerInterface $logger = null)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 0],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();
        $requestPath = $request->getPathInfo();
        
        // Only handle exceptions for our API routes
        if (!str_starts_with($requestPath, '/api')) {
            return;
        }

        $exception = $event->getThrowable();
        
        if ($this->logger) {
            $this->logger->error($exception->getMessage(), [
                'exception' => $exception,
                'request' => $requestPath,
            ]);
        }

        $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        $responseData = [
            'error' => 'An unexpected error occurred',
        ];

        if ($exception instanceof EntityNotFoundException) {
            $statusCode = Response::HTTP_NOT_FOUND;
            $responseData = [
                'error' => $exception->getMessage(),
            ];
        } elseif ($exception instanceof ValidationException) {
            $statusCode = Response::HTTP_BAD_REQUEST;
            $responseData = [
                'error' => 'Validation failed',
                'errors' => $exception->getErrors(),
            ];
        } elseif ($exception instanceof \InvalidArgumentException) {
            $statusCode = Response::HTTP_BAD_REQUEST;
            $responseData = [
                'error' => $exception->getMessage(),
            ];
        }

        $event->setResponse(new JsonResponse($responseData, $statusCode));
    }
}