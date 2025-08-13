<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Freema\ReactAdminApiBundle\DataProvider\DataProviderFactory;
use Freema\ReactAdminApiBundle\Event\Common\ResourceAccessEvent;
use Freema\ReactAdminApiBundle\Event\Common\ResponseEvent;
use Freema\ReactAdminApiBundle\Event\Crud\EntityCreatedEvent;
use Freema\ReactAdminApiBundle\Event\Crud\EntityDeletedEvent;
use Freema\ReactAdminApiBundle\Event\Crud\EntityUpdatedEvent;
use Freema\ReactAdminApiBundle\Event\List\PostListEvent;
use Freema\ReactAdminApiBundle\Event\List\PreListEvent;
use Freema\ReactAdminApiBundle\Exception\ValidationException;
use Freema\ReactAdminApiBundle\Interface\AdminEntityInterface;
use Freema\ReactAdminApiBundle\Interface\DataRepositoryCreateInterface;
use Freema\ReactAdminApiBundle\Interface\DataRepositoryDeleteInterface;
use Freema\ReactAdminApiBundle\Interface\DataRepositoryFindInterface;
use Freema\ReactAdminApiBundle\Interface\DataRepositoryListInterface;
use Freema\ReactAdminApiBundle\Interface\DataRepositoryUpdateInterface;
use Freema\ReactAdminApiBundle\Interface\DtoInterface;
use Freema\ReactAdminApiBundle\Dto\AdminApiDto;
use Freema\ReactAdminApiBundle\Request\CreateDataRequest;
use Freema\ReactAdminApiBundle\Request\DeleteDataRequest;
use Freema\ReactAdminApiBundle\Request\DeleteManyDataRequest;
use Freema\ReactAdminApiBundle\Request\UpdateDataRequest;
use Freema\ReactAdminApiBundle\Service\DtoFactory;
use Freema\ReactAdminApiBundle\Service\ResourceConfigurationService;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[Route]
class ResourceController extends AbstractController implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly ResourceConfigurationService $resourceConfig,
        private readonly DataProviderFactory $dataProviderFactory,
        private readonly DtoFactory $dtoFactory,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
        $this->setLogger(new NullLogger());
    }

    #[Route(path: '/{resource}', name: 'react_admin_api_resource_list', methods: ['GET'])]
    public function list(
        string $resource,
        Request $request,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        // Check access permissions
        if (!$this->dispatchResourceAccessEvent($resource, $request, 'list')) {
            return $this->createAccessDeniedResponse();
        }

        // Get appropriate data provider for the request
        $dataProvider = $this->dataProviderFactory->getProvider($request);

        // Transform request using data provider
        $requestData = $dataProvider->transformListRequest($request);

        // Dispatch pre-list event
        $preListEvent = new PreListEvent($resource, $request, $requestData);
        $this->eventDispatcher->dispatch($preListEvent, 'react_admin_api.pre_list');

        if ($preListEvent->isCancelled()) {
            return new JsonResponse(['error' => 'Operation cancelled'], Response::HTTP_FORBIDDEN);
        }

        // Use potentially modified request data
        $requestData = $preListEvent->getListDataRequest();
        $entityClass = $this->getResourceEntityClass($resource);

        $repository = $entityManager->getRepository($entityClass);
        if (!$repository instanceof DataRepositoryListInterface) {
            throw new \InvalidArgumentException('Repository does not implement DataRepositoryListInterface');
        }

        $responseData = $repository->list($requestData);

        // Dispatch post-list event
        $postListEvent = new PostListEvent($resource, $request, $requestData, $responseData);
        $this->eventDispatcher->dispatch($postListEvent, 'react_admin_api.post_list');

        // Use potentially modified result data
        $responseData = $postListEvent->getListDataResult();

        // Transform response using data provider
        $transformedData = $dataProvider->transformResponse(
            $responseData->getData(),
            $responseData->getTotal()
        );

        // Create response with Content-Range header for compatibility
        $response = new JsonResponse($transformedData);

        // Calculate range from offset/limit
        $offset = $requestData->getOffset() ?? 0;
        $limit = $requestData->getLimit() ?? 10;
        $endIndex = min($offset + $limit - 1, $responseData->getTotal() - 1);

        $response->headers->set('Content-Range', sprintf('items %d-%d/%d',
            $offset,
            $endIndex,
            $responseData->getTotal()
        ));
        $response->headers->set('X-Content-Range', (string) $responseData->getTotal());
        $response->headers->set('Access-Control-Expose-Headers', 'Content-Range, X-Content-Range');

        // Dispatch response event
        $responseEvent = new ResponseEvent($resource, $request, $response, 'list', $responseData);
        $this->eventDispatcher->dispatch($responseEvent, 'react_admin_api.response');

        return $responseEvent->getResponse();
    }

    #[Route(path: '/{resource}', name: 'react_admin_api_resource_delete_many', methods: ['DELETE'])]
    public function deleteMany(
        string $resource,
        Request $request,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $requestData = new DeleteManyDataRequest($request);
        $entityClass = $this->getResourceEntityClass($resource);

        $repository = $entityManager->getRepository($entityClass);
        if (!$repository instanceof DataRepositoryDeleteInterface) {
            throw new \InvalidArgumentException('Repository does not implement DataRepositoryDeleteInterface');
        }

        $responseData = $repository->deleteMany($requestData);

        return $responseData->createResponse();
    }

    #[Route(path: '/{resource}/{id}', name: 'react_admin_api_resource_get', methods: ['GET'])]
    public function getEntity(
        string $resource,
        string $id,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $entityClass = $this->getResourceEntityClass($resource);
        $repository = $entityManager->getRepository($entityClass);
        if (!$repository instanceof DataRepositoryFindInterface) {
            throw new \LogicException(sprintf('Repository %s must implement %s to use findWithDto method', get_class($repository), DataRepositoryFindInterface::class));
        }

        $entity = $repository->findWithDto($id);

        if (!$entity) {
            return new JsonResponse(
                ['error' => 'Entity not found'],
                Response::HTTP_NOT_FOUND
            );
        }

        return new JsonResponse(
            $entity->toArray(),
        );
    }

    #[Route(path: '/{resource}', name: 'react_admin_api_resource_create', methods: ['POST'])]
    public function create(
        string $resource,
        Request $request,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        if (null === $data) {
            return new JsonResponse(
                ['error' => 'Invalid JSON provided'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $resourceDtoClass = $this->getResourceDtoClass($resource);
        $entityClass = $this->getResourceEntityClass($resource);

        $dataDto = $this->dtoFactory->createFromArray($data, $resourceDtoClass);

        // Validate the DTO
        $violations = $validator->validate($dataDto);

        if (count($violations) > 0) {
            throw new ValidationException($violations);
        }

        if (!$dataDto instanceof AdminApiDto) {
            throw new \InvalidArgumentException('DTO must be instance of AdminApiDto');
        }

        $requestData = new CreateDataRequest($dataDto);

        $repository = $entityManager->getRepository($entityClass);
        if (!$repository instanceof DataRepositoryCreateInterface) {
            throw new \InvalidArgumentException('Repository does not implement DataRepositoryCreateInterface');
        }

        $responseData = $repository->create($requestData);

        // Dispatch entity created event
        $createEvent = new EntityCreatedEvent($resource, $request, $requestData, $responseData);
        $this->eventDispatcher->dispatch($createEvent, 'react_admin_api.entity_created');

        return $responseData->createResponse();
    }

    #[Route(path: '/{resource}/{id}', name: 'react_admin_api_resource_update', methods: ['PUT'])]
    public function update(
        string $resource,
        string $id,
        Request $request,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        if (null === $data) {
            return new JsonResponse(
                ['error' => 'Invalid JSON provided'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $resourceDtoClass = $this->getResourceDtoClass($resource);
        $entityClass = $this->getResourceEntityClass($resource);

        $dataDto = $this->dtoFactory->createFromArray($data, $resourceDtoClass);

        // Validate the DTO
        $violations = $validator->validate($dataDto);
        if (count($violations) > 0) {
            throw new ValidationException($violations);
        }

        if (!$dataDto instanceof AdminApiDto) {
            throw new \InvalidArgumentException('DTO must be instance of AdminApiDto');
        }

        // Get old entity before update for logging
        $oldEntity = null;
        try {
            $oldEntity = $entityManager->getRepository($entityClass)->find($id);
        } catch (\Exception $e) {
            // Ignore errors when getting old entity
        }

        $requestData = new UpdateDataRequest($id, $dataDto);

        $repository = $entityManager->getRepository($entityClass);
        if (!$repository instanceof DataRepositoryUpdateInterface) {
            throw new \InvalidArgumentException('Repository does not implement DataRepositoryUpdateInterface');
        }

        $responseData = $repository->update($requestData);

        // Dispatch entity updated event
        $oldEntityAdmin = $oldEntity instanceof AdminEntityInterface ? $oldEntity : null;
        $updateEvent = new EntityUpdatedEvent($resource, $request, $requestData, $oldEntityAdmin, $responseData);
        $this->eventDispatcher->dispatch($updateEvent, 'react_admin_api.entity_updated');

        return $responseData->createResponse();
    }

    #[Route(path: '/{resource}/{id}', name: 'react_admin_api_resource_delete', methods: ['DELETE'])]
    public function delete(
        string $resource,
        string $id,
        Request $request,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $entityClass = $this->getResourceEntityClass($resource);

        // Get entity before deletion for logging
        $deletedEntity = null;
        try {
            $deletedEntity = $entityManager->getRepository($entityClass)->find($id);
        } catch (\Exception $e) {
            // Ignore errors when getting entity
        }

        $requestData = new DeleteDataRequest($id);

        $repository = $entityManager->getRepository($entityClass);
        if (!$repository instanceof DataRepositoryDeleteInterface) {
            throw new \InvalidArgumentException('Repository does not implement DataRepositoryDeleteInterface');
        }

        $responseData = $repository->delete($requestData);

        // Dispatch entity deleted event
        $deletedEntityAdmin = $deletedEntity instanceof AdminEntityInterface ? $deletedEntity : null;
        $deleteEvent = new EntityDeletedEvent($resource, $request, $requestData, $deletedEntityAdmin, $responseData);
        $this->eventDispatcher->dispatch($deleteEvent, 'react_admin_api.entity_deleted');

        return $responseData->createResponse();
    }

    /**
     * Get the resource entity class for the given resource path.
     *
     * @return class-string
     */
    private function getResourceEntityClass(string $resource): string
    {
        return $this->resourceConfig->getResourceEntityClass($resource);
    }

    /**
     * Get the resource DTO class for the given resource path.
     *
     * @return class-string<DtoInterface>
     */
    private function getResourceDtoClass(string $resource): string
    {
        return $this->resourceConfig->getResourceDtoClass($resource);
    }

    /**
     * Dispatch resource access event and check if operation is allowed
     */
    private function dispatchResourceAccessEvent(string $resource, Request $request, string $operation, ?string $resourceId = null): bool
    {
        $accessEvent = new ResourceAccessEvent($resource, $request, $operation, $resourceId);
        $this->eventDispatcher->dispatch($accessEvent, 'react_admin_api.resource_access');

        return !$accessEvent->isCancelled();
    }

    /**
     * Create access denied response
     */
    private function createAccessDeniedResponse(): JsonResponse
    {
        return new JsonResponse(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
    }
}
