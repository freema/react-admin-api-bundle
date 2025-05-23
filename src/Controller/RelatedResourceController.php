<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Freema\ReactAdminApiBundle\Interface\RelatedDataRepositoryListInterface;
use Freema\ReactAdminApiBundle\Interface\RelatedEntityInterface;
use Freema\ReactAdminApiBundle\Request\ListDataRequest;
use Freema\ReactAdminApiBundle\Request\ListDataRequestFactory;
use Freema\ReactAdminApiBundle\Service\ResourceConfigurationService;

#[Route]
class RelatedResourceController extends AbstractController implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly ResourceConfigurationService $resourceConfig,
        private readonly ListDataRequestFactory $listDataRequestFactory
    ) {
        $this->setLogger(new NullLogger());
    }

    #[Route(path: '/{resource}/{id}/{relatedResource}', name: 'react_admin_api_related_resource_list', methods: ['GET'])]
    public function list(
        string $resource,
        string $id,
        string $relatedResource,
        EntityManagerInterface $entityManager,
        Request $request
    ): JsonResponse {
        $requestData = $this->listDataRequestFactory->createFromRequest($request);
        
        // Get parent entity
        $resourceEntityClass = $this->getResourceEntityClass($resource);
        $resourceRepository = $entityManager->getRepository($resourceEntityClass);
        $entity = $resourceRepository->find($id);
        
        if (!$entity) {
            return new JsonResponse(['error' => 'Parent entity not found'], Response::HTTP_NOT_FOUND);
        }
        
        if (!$entity instanceof RelatedEntityInterface) {
            return new JsonResponse([
                'error' => sprintf('Entity %s must implement RelatedEntityInterface to be used in related resources', get_class($entity))
            ], Response::HTTP_BAD_REQUEST);
        }

        // Get related repository
        $relatedResourceEntityClass = $this->getResourceEntityClass($relatedResource);
        $relatedResourceRepository = $entityManager->getRepository($relatedResourceEntityClass);
        
        if (!$relatedResourceRepository instanceof RelatedDataRepositoryListInterface) {
            return new JsonResponse([
                'error' => sprintf('Repository %s must implement RelatedDataRepositoryListInterface', get_class($relatedResourceRepository))
            ], Response::HTTP_BAD_REQUEST);
        }
        
        $responseData = $relatedResourceRepository->listRelatedTo($requestData, $entity);

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
}