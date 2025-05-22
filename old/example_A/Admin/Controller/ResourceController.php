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
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Vlp\Mailer\Api\Admin\Dto\AdminApiDto;
use Vlp\Mailer\Api\Admin\Dto\AdminDto;
use Vlp\Mailer\Api\Admin\Dto\AdminProjectRightDto;
use Vlp\Mailer\Api\Admin\Dto\CampaignDto;
use Vlp\Mailer\Api\Admin\Dto\MailerDsnDto;
use Vlp\Mailer\Api\Admin\Dto\ProjectDto;
use Vlp\Mailer\Api\Admin\Dto\TemplateBlockDto;
use Vlp\Mailer\Api\Admin\Dto\TemplateDto;
use Vlp\Mailer\Api\Admin\Dto\TemplateRendererDto;
use Vlp\Mailer\Api\Admin\Dto\TemplateRevisionDto;
use Vlp\Mailer\Api\Admin\Interface\DataRepositoryCreateInterface;
use Vlp\Mailer\Api\Admin\Interface\DataRepositoryDeleteInterface;
use Vlp\Mailer\Api\Admin\Interface\DataRepositoryFindInterface;
use Vlp\Mailer\Api\Admin\Interface\DataRepositoryListInterface;
use Vlp\Mailer\Api\Admin\Interface\DataRepositoryUpdateInterface;
use Vlp\Mailer\Api\Admin\Request\CreateDataRequest;
use Vlp\Mailer\Api\Admin\Request\DeleteDataRequest;
use Vlp\Mailer\Api\Admin\Request\DeleteManyDataRequest;
use Vlp\Mailer\Api\Admin\Request\ListDataRequest;
use Vlp\Mailer\Api\Admin\Request\UpdateDataRequest;

#[Route(path: '/admin/api/v1')]
class ResourceController extends AbstractController implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    #[Route(path: '/{resource}', name: 'admin_api_resource_list', methods: ['GET'])]
    public function list(
        string $resource,
        EntityManagerInterface $entityManager,
        Request $request,
    ): JsonResponse {
        $requestData = new ListDataRequest($request);
        $entityClass = $this->getResourceEntityClass($resource);

        $repository = $entityManager->getRepository($entityClass);
        if (!$repository instanceof DataRepositoryListInterface) {
            throw new \InvalidArgumentException('Repository does not implement DataRepositoryListInterface');
        }

        $responseData = $repository->list($requestData);

        return $responseData->createResponse();
    }

    #[Route(path: '/{resource}', name: 'admin_api_resource_delete_many', methods: ['DELETE'])]
    public function deleteMany(
        string $resource,
        EntityManagerInterface $entityManager,
        Request $request,
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

    #[Route(path: '/{resource}/{id}', name: 'admin_api_resource_get', methods: ['GET'])]
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

    #[Route(path: '/{resource}', name: 'admin_api_resource_create', methods: ['POST'])]
    public function create(
        string $resource,
        EntityManagerInterface $entityManager,
        DenormalizerInterface $denormalizer,
        Request $request,
        ValidatorInterface $validator,
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

        $dataDto = $denormalizer->denormalize($data, $resourceDtoClass);
        $requestData = new CreateDataRequest($dataDto);

        $repository = $entityManager->getRepository($entityClass);
        if (!$repository instanceof DataRepositoryCreateInterface) {
            throw new \InvalidArgumentException('Repository does not implement DataRepositoryCreateInterface');
        }

        $responseData = $repository->create($requestData);

        return $responseData->createResponse();
    }

    #[Route(path: '/{resource}/{id}', name: 'admin_api_resource_update', methods: ['PUT'])]
    public function update(
        string $resource,
        string $id,
        DenormalizerInterface $denormalizer,
        EntityManagerInterface $entityManager,
        Request $request,
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

        $dataDto = $denormalizer->denormalize($data, $resourceDtoClass);
        $requestData = new UpdateDataRequest($id, $dataDto);

        $repository = $entityManager->getRepository($entityClass);
        if (!$repository instanceof DataRepositoryUpdateInterface) {
            throw new \InvalidArgumentException('Repository does not implement DataRepositoryUpdateInterface');
        }

        $responseData = $repository->update($requestData);

        return $responseData->createResponse();
    }

    #[Route(path: '/{resource}/{id}', name: 'admin_api_resource_delete', methods: ['DELETE'])]
    public function delete(
        string $resource,
        string $id,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $entityClass = $this->getResourceEntityClass($resource);

        $repository = $entityManager->getRepository($entityClass);
        if (!$repository instanceof DataRepositoryDeleteInterface) {
            throw new \InvalidArgumentException('Repository does not implement DataRepositoryDeleteInterface');
        }

        $responseData = $repository->delete(new DeleteDataRequest($id));

        return $responseData->createResponse();
    }

    private function getAllowedResourceDto(): array
    {
        return [
            'projects' => ProjectDto::class,
            'campaigns' => CampaignDto::class,
            'templates' => TemplateDto::class,
            'template-renderers' => TemplateRendererDto::class,
            'admins' => AdminDto::class,
            'admin-project-rights' => AdminProjectRightDto::class,
            'template-blocks' => TemplateBlockDto::class,
            'template-revisions' => TemplateRevisionDto::class,
            'mailer-dsns' => MailerDsnDto::class,
        ];
    }

    /**
     * @return class-string<AdminApiDto>
     */
    private function getResourceDtoClass(string $resource): string
    {
        $allowedResources = $this->getAllowedResourceDto();
        if (!array_key_exists($resource, $allowedResources)) {
            throw new \InvalidArgumentException(sprintf('Resource not allowed %s', $resource));
        }

        $resourceDtoClass = $allowedResources[$resource];
        if (!is_subclass_of($resourceDtoClass, AdminApiDto::class)) {
            throw new \LogicException('Resource DTO class must extend AdminApiDto class');
        }

        return $resourceDtoClass;
    }

    private function getResourceEntityClass(string $resource): string
    {
        $resourceDtoClass = $this->getResourceDtoClass($resource);
        if (!is_subclass_of($resourceDtoClass, AdminApiDto::class)) {
            throw new \LogicException('Resource DTO class must extend AdminApiDto class');
        }

        return $resourceDtoClass::getMappedEntityClass();
    }
}
