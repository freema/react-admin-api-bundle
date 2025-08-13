<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\EventListener;

use Doctrine\ORM\Query\QueryException;
use Freema\ReactAdminApiBundle\Exception\AdminAccessDeniedException;
use Freema\ReactAdminApiBundle\Exception\EntityNotFoundException;
use Freema\ReactAdminApiBundle\Exception\ValidationException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
class ApiExceptionListener implements EventSubscriberInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
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

        $this->logger?->error($exception->getMessage(), [
            'exception' => $exception,
            'request' => $request->getPathInfo(),
        ]);

        $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        $responseData = [
            'error' => $this->debugMode ? $exception->getMessage() : 'An unexpected error occurred',
        ];

        // Only add debug info in development mode
        if ($this->debugMode) {
            $responseData['debug'] = [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ];
        }

        if ($exception instanceof AdminAccessDeniedException) {
            $statusCode = Response::HTTP_UNAUTHORIZED;
            $responseData = [
                'error' => 'ADMIN_ACCESS_DENIED',
                'message' => $exception->getMessage(),
            ];
        } elseif ($exception instanceof EntityNotFoundException) {
            $statusCode = Response::HTTP_NOT_FOUND;
            $responseData = [
                'error' => $exception->getMessage(),
            ];
        } elseif ($exception instanceof ValidationException) {
            $statusCode = Response::HTTP_BAD_REQUEST;
            $errors = $exception->getErrors();

            // Format errors for better readability
            $formattedErrors = [];
            $detailedErrors = [];

            foreach ($errors as $field => $fieldError) {
                if (is_array($fieldError)) {
                    // Check if it's a detailed error or array of errors
                    if (isset($fieldError['message'])) {
                        // Single detailed error
                        $formattedErrors[$field] = $this->formatDetailedError($fieldError);
                        $detailedErrors[$field] = $fieldError;
                    } else {
                        // Multiple errors for the same field
                        $messages = [];
                        $details = [];
                        foreach ($fieldError as $error) {
                            if (is_array($error) && isset($error['message'])) {
                                $messages[] = $this->formatDetailedError($error);
                                $details[] = $error;
                            } else {
                                $messages[] = (string) $error;
                            }
                        }
                        $formattedErrors[$field] = implode('; ', $messages);
                        $detailedErrors[$field] = $details;
                    }
                } else {
                    $formattedErrors[$field] = (string) $fieldError;
                }
            }

            $responseData = [
                'error' => 'VALIDATION_ERROR',
                'message' => 'Validation failed',
                'errors' => $formattedErrors,
            ];

            // Always include detailed errors for better debugging
            $responseData['details'] = $detailedErrors;

            // Add debug info if enabled
            if ($this->debugMode) {
                $responseData['debug'] = [
                    'raw_errors' => $errors,
                ];
            }
        } elseif ($exception instanceof \InvalidArgumentException) {
            $statusCode = Response::HTTP_BAD_REQUEST;
            $responseData = [
                'error' => $exception->getMessage(),
            ];
        } elseif ($exception instanceof QueryException) {
            $statusCode = Response::HTTP_BAD_REQUEST;
            $responseData = [
                'error' => $this->debugMode ? $exception->getMessage() : 'Invalid query parameter',
            ];

            // Only add debug info in development mode for SQL errors
            if ($this->debugMode) {
                $responseData['debug'] = [
                    'message' => $exception->getMessage(),
                    'type' => 'QueryException',
                ];
            }
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

    private function formatDetailedError(array $error): string
    {
        $message = $error['message'] ?? 'Unknown error';

        // Add value information
        if (isset($error['value'])) {
            $value = is_scalar($error['value']) ? $error['value'] : json_encode($error['value']);
            $message .= sprintf(' (received: "%s")', $value);
        }

        // Add allowed values if available
        if (isset($error['allowed_values']) && is_array($error['allowed_values'])) {
            $allowedStr = implode(', ', array_map(function ($v) {
                return '"'.$v.'"';
            }, $error['allowed_values']));
            $message .= sprintf(' [allowed values: %s]', $allowedStr);
        }

        return $message;
    }
}
