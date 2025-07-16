<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Freema\ReactAdminApiBundle\Exception\ValidationException;
use Freema\ReactAdminApiBundle\Interface\DtoInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Freema\ReactAdminApiBundle\Service\DtoFactory;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Freema\ReactAdminApiBundle\Interface\DataRepositoryCreateInterface;
use Freema\ReactAdminApiBundle\Interface\DataRepositoryDeleteInterface;
use Freema\ReactAdminApiBundle\Interface\DataRepositoryFindInterface;
use Freema\ReactAdminApiBundle\Interface\DataRepositoryListInterface;
use Freema\ReactAdminApiBundle\Interface\DataRepositoryUpdateInterface;
use Freema\ReactAdminApiBundle\Request\CreateDataRequest;
use Freema\ReactAdminApiBundle\Request\DeleteDataRequest;
use Freema\ReactAdminApiBundle\Request\DeleteManyDataRequest;
use Freema\ReactAdminApiBundle\Request\ListDataRequest;
use Freema\ReactAdminApiBundle\Request\ListDataRequestFactory;
use Freema\ReactAdminApiBundle\Request\UpdateDataRequest;
use Freema\ReactAdminApiBundle\Service\ResourceConfigurationService;
use Freema\ReactAdminApiBundle\DataProvider\DataProviderFactory;

#[Route]
class ResourceController extends AbstractController implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly ResourceConfigurationService $resourceConfig,
        private readonly ListDataRequestFactory $listDataRequestFactory,
        private readonly DataProviderFactory $dataProviderFactory,
        private readonly DtoFactory $dtoFactory
    ) {
        $this->setLogger(new NullLogger());
    }

    #[Route(path: '/{resource}', name: 'react_admin_api_resource_list', methods: ['GET'])]
    public function list(
        string $resource,
        Request $request,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        // Get appropriate data provider for the request
        $dataProvider = $this->dataProviderFactory->getProvider($request);
        
        // Transform request using data provider
        $requestData = $dataProvider->transformListRequest($request);
        $entityClass = $this->getResourceEntityClass($resource);

        $repository = $entityManager->getRepository($entityClass);
        if (!$repository instanceof DataRepositoryListInterface) {
            throw new \InvalidArgumentException('Repository does not implement DataRepositoryListInterface');
        }

        $responseData = $repository->list($requestData);

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

        return $response;
    }

    #[Route(path: '/{resource}', name: 'react_admin_api_resource_delete_many', methods: ['DELETE'])]
    public function deleteMany(
        string $resource,
        Request $request,
        EntityManagerInterface $entityManager
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
        EntityManagerInterface $entityManager
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
        EntityManagerInterface $entityManager
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
        
        $requestData = new CreateDataRequest($dataDto);

        $repository = $entityManager->getRepository($entityClass);
        if (!$repository instanceof DataRepositoryCreateInterface) {
            throw new \InvalidArgumentException('Repository does not implement DataRepositoryCreateInterface');
        }

        $responseData = $repository->create($requestData);

        return $responseData->createResponse();
    }

    #[Route(path: '/{resource}/{id}', name: 'react_admin_api_resource_update', methods: ['PUT'])]
    public function update(
        string $resource,
        string $id,
        Request $request,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager
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
        
        $requestData = new UpdateDataRequest($id, $dataDto);

        $repository = $entityManager->getRepository($entityClass);
        if (!$repository instanceof DataRepositoryUpdateInterface) {
            throw new \InvalidArgumentException('Repository does not implement DataRepositoryUpdateInterface');
        }

        $responseData = $repository->update($requestData);

        return $responseData->createResponse();
    }

    #[Route(path: '/{resource}/{id}', name: 'react_admin_api_resource_delete', methods: ['DELETE'])]
    public function delete(
        string $resource,
        string $id,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $entityClass = $this->getResourceEntityClass($resource);

        $repository = $entityManager->getRepository($entityClass);
        if (!$repository instanceof DataRepositoryDeleteInterface) {
            throw new \InvalidArgumentException('Repository does not implement DataRepositoryDeleteInterface');
        }

        $responseData = $repository->delete(new DeleteDataRequest($id));

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
}