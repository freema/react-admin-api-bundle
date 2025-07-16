<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\EventListener;

use Freema\ReactAdminApiBundle\Exception\EntityNotFoundException;
use Freema\ReactAdminApiBundle\Exception\ValidationException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;

class ApiExceptionListener implements EventSubscriberInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly RouterInterface $router,
        private readonly bool $enabled,
        private readonly bool $debugMode,
    ) {
        $this->setLogger(new NullLogger());
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 0],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if (!$this->enabled) {
            return;
        }

        $request = $event->getRequest();
        
        // Only handle exceptions for our bundle's routes
        if (!$this->isReactAdminApiRoute($request)) {
            return;
        }

        $exception = $event->getThrowable();
        
        $this->logger->error($exception->getMessage(), [
            'exception' => $exception,
            'request' => $request->getPathInfo(),
        ]);

        $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        $responseData = [
            'error' => $this->debugMode ? $exception->getMessage() : 'An unexpected error occurred',
        ];

        if ($this->debugMode) {
            $responseData['debug'] = [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ];
        }

        if ($exception instanceof EntityNotFoundException) {
            $statusCode = Response::HTTP_NOT_FOUND;
            $responseData = [
                'error' => $exception->getMessage(),
            ];
        } elseif ($exception instanceof ValidationException) {
            $statusCode = Response::HTTP_BAD_REQUEST;
            $responseData = [
                'error' => 'VALIDATION_ERROR',
                'message' => 'Validation failed',
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

    private function isReactAdminApiRoute(Request $request): bool
    {
        $routeName = $request->attributes->get('_route');
        
        if (!$routeName) {
            return false;
        }
        
        // Check if the route belongs to our bundle
        return str_starts_with($routeName, 'react_admin_api_');
    }
}