<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Event\Common;

use Freema\ReactAdminApiBundle\Event\ReactAdminApiEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Event dispatched before sending the response
 * Allows modification of headers, status codes, and response data
 */
class ResponseEvent extends ReactAdminApiEvent
{
    public function __construct(
        string $resource,
        Request $request,
        private JsonResponse $response,
        private readonly string $operation,
        private readonly mixed $originalData = null,
    ) {
        parent::__construct($resource, $request);
    }

    /**
     * Get the response object
     */
    public function getResponse(): JsonResponse
    {
        return $this->response;
    }

    /**
     * Set the response object
     */
    public function setResponse(JsonResponse $response): self
    {
        $this->response = $response;

        return $this;
    }

    /**
     * Get the operation that was performed
     */
    public function getOperation(): string
    {
        return $this->operation;
    }

    /**
     * Get the original data before transformation
     */
    public function getOriginalData(): mixed
    {
        return $this->originalData;
    }

    /**
     * Get the response data
     */
    public function getResponseData(): array
    {
        $content = $this->response->getContent();
        if ($content === false) {
            return [];
        }
        return json_decode($content, true) ?? [];
    }

    /**
     * Set the response data
     */
    public function setResponseData(array $data): self
    {
        $this->response->setData($data);

        return $this;
    }

    /**
     * Add data to the response
     */
    public function addResponseData(string $key, mixed $value): self
    {
        $data = $this->getResponseData();
        $data[$key] = $value;
        $this->setResponseData($data);

        return $this;
    }

    /**
     * Remove data from the response
     */
    public function removeResponseData(string $key): self
    {
        $data = $this->getResponseData();
        unset($data[$key]);
        $this->setResponseData($data);

        return $this;
    }

    /**
     * Get the response status code
     */
    public function getStatusCode(): int
    {
        return $this->response->getStatusCode();
    }

    /**
     * Set the response status code
     */
    public function setStatusCode(int $statusCode): self
    {
        $this->response->setStatusCode($statusCode);

        return $this;
    }

    /**
     * Get response headers
     */
    public function getHeaders(): array
    {
        return $this->response->headers->all();
    }

    /**
     * Add a header to the response
     */
    public function addHeader(string $name, string $value): self
    {
        $this->response->headers->set($name, $value);

        return $this;
    }

    /**
     * Remove a header from the response
     */
    public function removeHeader(string $name): self
    {
        $this->response->headers->remove($name);

        return $this;
    }

    /**
     * Add CORS headers
     */
    public function addCorsHeaders(array $allowedOrigins = ['*'], array $allowedMethods = ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS']): self
    {
        $this->response->headers->set('Access-Control-Allow-Origin', implode(', ', $allowedOrigins));
        $this->response->headers->set('Access-Control-Allow-Methods', implode(', ', $allowedMethods));
        $this->response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');

        return $this;
    }

    /**
     * Add caching headers
     */
    public function addCachingHeaders(int $maxAge = 0, bool $public = false): self
    {
        $this->response->headers->set('Cache-Control', ($public ? 'public' : 'private').', max-age='.$maxAge);

        if ($maxAge > 0) {
            $this->response->headers->set('Expires', (new \DateTimeImmutable('+'.$maxAge.' seconds'))->format('D, d M Y H:i:s \G\M\T'));
        }

        return $this;
    }

    /**
     * Add metadata to the response
     */
    public function addMetadata(array $metadata): self
    {
        $data = $this->getResponseData();
        $data['_metadata'] = array_merge($data['_metadata'] ?? [], $metadata);
        $this->setResponseData($data);

        return $this;
    }

    /**
     * Add timing information to the response
     */
    public function addTimingInfo(\DateTimeInterface $startTime): self
    {
        $endTime = new \DateTimeImmutable();
        $duration = $endTime->getTimestamp() - $startTime->getTimestamp();

        $this->addMetadata([
            'timing' => [
                'start' => $startTime->format('c'),
                'end' => $endTime->format('c'),
                'duration' => $duration.'ms',
            ],
        ]);

        return $this;
    }

    /**
     * Check if the response indicates success
     */
    public function isSuccessful(): bool
    {
        return $this->getStatusCode() >= 200 && $this->getStatusCode() < 300;
    }

    /**
     * Check if the response indicates an error
     */
    public function isError(): bool
    {
        return $this->getStatusCode() >= 400;
    }
}
