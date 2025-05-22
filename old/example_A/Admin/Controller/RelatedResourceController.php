<?php
declare(strict_types=1);

namespace Vlp\Mailer\Api\Admin\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Vlp\Mailer\Api\Admin\Dto\AdminApiDto;
use Vlp\Mailer\Api\Admin\Dto\TemplateDto;
use Vlp\Mailer\Api\Admin\Dto\TemplateRevisionDto;
use Vlp\Mailer\Api\Admin\Interface\RelatedDataRepositoryListInterface;
use Vlp\Mailer\Api\Admin\Request\ListDataRequest;

#[Route(path: '/admin/api/v1')]
class RelatedResourceController extends AbstractController implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    #[Route(path: '/{resource}/{id}/{relatedResource}', name: 'admin_api_related_resource_list', methods: ['GET'])]
    public function list(string $resource, int $id, string $relatedResource, EntityManagerInterface $entityManager, Request $request)
    {
        $requestData = new ListDataRequest($request);
        $entityClass = $this->getResourceEntityClass($resource);
        if ($entityClass instanceof JsonResponse) {
            return $entityClass;
        }

        $resourceRepository = $entityManager->getRepository($entityClass);
        $entity = $resourceRepository->find($id);

        $relatedResourceRepository = $entityManager->getRepository($this->getResourceEntityClass($relatedResource));
        if (false === $relatedResourceRepository instanceof RelatedDataRepositoryListInterface) {
            throw new \InvalidArgumentException('Repository does not implement RelatedDataRepositoryListInterface');
        }
        $responseData = $relatedResourceRepository->listRelatedTo($requestData, $entity);

        return $responseData->createResponse();
    }

    private function getAllowedResourceDto(): array
    {
        return [
            'templates' => TemplateDto::class,
            'revisions' => TemplateRevisionDto::class,
        ];
    }

    private function getResourceEntityClass(string $resource): string|JsonResponse
    {
        if (!in_array($resource, array_keys($this->getAllowedResourceDto()), true)) {
            return new JsonResponse(['error' => 'Invalid resource'], Response::HTTP_BAD_REQUEST);
        }
        $resourceDtoClass = $this->getAllowedResourceDto()[$resource];
        if (false === is_subclass_of($resourceDtoClass, AdminApiDto::class)) {
            throw new \LogicException('resource dto class must extend AdminApiDto class');
        }

        return $resourceDtoClass::getMappedEntityClass();
    }
}
