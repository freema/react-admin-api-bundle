<?php

namespace DenikProfile\Newsletter\Promo\Admin\Repository;

use DateTimeImmutable;
use DenikProfile\Entity\PromoCreative;
use DenikProfile\Newsletter\Promo\Admin\CreateTrait;
use DenikProfile\Newsletter\Promo\Admin\DeleteTrait;
use DenikProfile\Newsletter\Promo\Admin\Dto\AdminApiDto;
use DenikProfile\Newsletter\Promo\Admin\Dto\PromoCreativeDto;
use DenikProfile\Newsletter\Promo\Admin\Interface\AdminEntityInterface;
use DenikProfile\Newsletter\Promo\Admin\Interface\DataRepositoryCreateInterface;
use DenikProfile\Newsletter\Promo\Admin\Interface\DataRepositoryDeleteInterface;
use DenikProfile\Newsletter\Promo\Admin\Interface\DataRepositoryListInterface;
use DenikProfile\Newsletter\Promo\Admin\Interface\DataRepositoryUpdateInterface;
use DenikProfile\Newsletter\Promo\Admin\ListTrait;
use DenikProfile\Newsletter\Promo\Admin\Request\DeleteManyDataRequest;
use DenikProfile\Newsletter\Promo\Admin\Result\DeleteDataResult;
use DenikProfile\Newsletter\Promo\Admin\UpdateTrait;
use DenikProfile\Newsletter\Promo\Images\CreativeImageStorage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class PromoCreativeRepository extends ServiceEntityRepository implements
    DataRepositoryListInterface,
    DataRepositoryCreateInterface,
    DataRepositoryUpdateInterface,
    DataRepositoryDeleteInterface
{
    use ListTrait;
    use CreateTrait;
    use UpdateTrait;
    use DeleteTrait;

    public function __construct(ManagerRegistry $registry, private CreativeImageStorage $creativeImageStorage, private RouterInterface $router)
    {
        parent::__construct($registry, PromoCreative::class);
    }

    public function getFullSearchFields(): array
    {
        return ['description', 'title', 'link', 'imageUrl', 'category', 'name', 'mobileImageUrl'];
    }

    public static function mapToDto(AdminEntityInterface|PromoCreative $entity): AdminApiDto
    {
        return PromoCreativeDto::createFromEntity($entity);
    }

    public function getRepository()
    {
        return $this->getEntityManager()->getRepository(PromoCreative::class);
    }

    /** @return PromoCreative[] */
    public function createEntitiesFromDto(AdminApiDto|PromoCreativeDto $dto): array
    {
        $imageUrl = null;
        $mobileImageUrl = null;
        if ($dto->getImage()) {
            $imageUrl = sprintf('%s', $this->router->generate('newsletter_get_image', ['imageRelativePath' => $this->creativeImageStorage->store($dto->getImage())], UrlGeneratorInterface::ABSOLUTE_URL));
        }
        if ($dto->getMobileImage()) {
            $mobileImageUrl = sprintf('%s', $this->router->generate('newsletter_get_image', ['imageRelativePath' => $this->creativeImageStorage->store($dto->getMobileImage())], UrlGeneratorInterface::ABSOLUTE_URL), );
        }

        return [new PromoCreative(
            $dto->getName(),
            $dto->getTitle(),
            $dto->getLink(),
            $imageUrl ?? null,
            $mobileImageUrl ?? null,
            $dto->getCategory(),
            $dto->getDescription(),
            $dto->isDisabled()
        )];
    }

    public function updateEntityFromDto(PromoCreative $entity, AdminApiDto|PromoCreativeDto $dto): PromoCreative
    {
        $imageUrl = null;
        $mobileImageUrl = null;
        if ($dto->getImage()) {
            $imageUrl = sprintf('%s', $this->router->generate('newsletter_get_image', ['imageRelativePath' => $this->creativeImageStorage->store($dto->getImage())], UrlGeneratorInterface::ABSOLUTE_URL));
        }
        if ($dto->getMobileImage()) {
            $mobileImageUrl = sprintf('%s', $this->router->generate('newsletter_get_image', ['imageRelativePath' => $this->creativeImageStorage->store($dto->getMobileImage())], UrlGeneratorInterface::ABSOLUTE_URL), );
        }

        $entity->setName($dto->getName());
        $entity->setTitle($dto->getTitle());
        $entity->setLink($dto->getLink());
        $entity->setImageUrl($imageUrl ?? $dto->getImageUrl());
        $entity->setMobileImageUrl($mobileImageUrl ?? $dto->getMobileImageUrl());
        $entity->setCategory($dto->getCategory());
        $entity->setDescription($dto->getDescription());
        $entity->setDisabled($dto->isDisabled());
        $entity->setUpdatedAt(new DateTimeImmutable());

        return $entity;
    }

    public function deleteMany(DeleteManyDataRequest $dataRequest): DeleteDataResult
    {
        $entityManager = $this->getEntityManager();
        if (false === ($entityManager instanceof EntityManagerInterface)) {
            throw new \LogicException();
        }

        $entities = $this->findBy(['id' => $dataRequest->getIds()]);

        if (empty($entities)) {
            return $dataRequest->createResult(
                status: false,
                errorMessages: ['No entities deleted']
            );
        }

        /** @var PromoCreative $entity */
        foreach ($entities as $entity) {
            $entity->setDisabled(true);
            $entityManager->persist($entity);
        }
        $entityManager->flush();

        return $dataRequest->createResult(true);
    }
}
