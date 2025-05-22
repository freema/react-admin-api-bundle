<?php
declare(strict_types=1);

namespace DenikProfile\Newsletter\Promo\Admin\Repository;

use DenikProfile\Entity\MailingList;
use DenikProfile\Entity\NewsletterCampaign;
use DenikProfile\Newsletter\Promo\Admin\CreateTrait;
use DenikProfile\Newsletter\Promo\Admin\Dto\AdminApiDto;
use DenikProfile\Newsletter\Promo\Admin\Dto\MailingListDto;
use DenikProfile\Newsletter\Promo\Admin\Interface\AdminEntityInterface;
use DenikProfile\Newsletter\Promo\Admin\Interface\DataRepositoryCreateInterface;
use DenikProfile\Newsletter\Promo\Admin\Interface\DataRepositoryListInterface;
use DenikProfile\Newsletter\Promo\Admin\Interface\DataRepositoryUpdateInterface;
use DenikProfile\Newsletter\Promo\Admin\Interface\RelatedDataRepositoryListInterface;
use DenikProfile\Newsletter\Promo\Admin\ListRelatedToTrait;
use DenikProfile\Newsletter\Promo\Admin\ListTrait;
use DenikProfile\Newsletter\Promo\Admin\UpdateTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MailingListRepository extends ServiceEntityRepository implements
    DataRepositoryListInterface,
    DataRepositoryUpdateInterface,
    DataRepositoryCreateInterface,
    RelatedDataRepositoryListInterface
{
    use ListTrait;
    use UpdateTrait;
    use CreateTrait;
    use ListRelatedToTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MailingList::class);
    }

    public function getFullSearchFields(): array
    {
        return ['title', 'reglogSource'];
    }

    public static function mapToDto(AdminEntityInterface|MailingList $entity): AdminApiDto
    {
        return MailingListDto::createFromEntity($entity);
    }

    public function getRepository(): \Doctrine\ORM\EntityRepository
    {
        return $this->getEntityManager()->getRepository(MailingList::class);
    }

    /**
     * Creates new MailingList entities from DTO.
     *
     * @param AdminApiDto|MailingListDto $dto
     *
     * @return MailingList[]
     *
     * @throws \InvalidArgumentException If newsletter campaign is not found
     */
    public function createEntitiesFromDto(AdminApiDto|MailingListDto $dto): array
    {
        if (!$dto->newsletterCampaignId) {
            throw new \InvalidArgumentException('Newsletter campaign ID is required');
        }

        $newsletterCampaign = $this->getEntityManager()
            ->getRepository(NewsletterCampaign::class)
            ->find($dto->newsletterCampaignId);

        if (!$newsletterCampaign) {
            throw new \InvalidArgumentException('Newsletter campaign not found');
        }

        $entity = new MailingList(
            $newsletterCampaign,
            $dto->title
        );

        if (null !== $dto->pianoId) {
            $entity->setPianoId($dto->pianoId);
        }

        if (null !== $dto->ecomailId) {
            $entity->setEcomailId($dto->ecomailId);
        }

        if (null !== $dto->ecomailTags) {
            $entity->setEcomailTags($dto->ecomailTags);
        }

        if (null !== $dto->solarTags) {
            $entity->setSolarTags($dto->solarTags);
        }

        if (null !== $dto->solarConsents) {
            $entity->setSolarConsents($dto->solarConsents);
        }

        if (null !== $dto->reglogSource) {
            $entity->setReglogSource($dto->reglogSource);
        }

        if (null !== $dto->sendConfirmationEmail) {
            $entity->setSendConfirmationEmail($dto->sendConfirmationEmail);
        }

        if (null !== $dto->solarProcessings) {
            $entity->setSolarProcessings($dto->solarProcessings);
        }

        return [$entity];
    }

    /**
     * Updates entity from DTO.
     */
    public function updateEntityFromDto(MailingList $entity, AdminApiDto|MailingListDto $dto): MailingList
    {
        if (null !== $dto->newsletterCampaignId && $entity->getNewsletterCampaign()->getId() !== $dto->newsletterCampaignId) {
            $newsletterCampaign = $this->getEntityManager()
                ->getRepository(NewsletterCampaign::class)
                ->find($dto->newsletterCampaignId);

            if (!$newsletterCampaign) {
                throw new \InvalidArgumentException('Newsletter campaign not found');
            }

            $entity->setNewsletterCampaign($newsletterCampaign);
        }

        $entity->setTitle($dto->title);

        if (null !== $dto->pianoId) {
            $entity->setPianoId($dto->pianoId);
        }

        if (null !== $dto->ecomailId) {
            $entity->setEcomailId($dto->ecomailId);
        }

        if (null !== $dto->ecomailTags) {
            $entity->setEcomailTags($dto->ecomailTags);
        }

        if (null !== $dto->solarTags) {
            $entity->setSolarTags($dto->solarTags);
        }

        if (null !== $dto->solarConsents) {
            $entity->setSolarConsents($dto->solarConsents);
        }

        if (null !== $dto->reglogSource) {
            $entity->setReglogSource($dto->reglogSource);
        }

        if (null !== $dto->sendConfirmationEmail) {
            $entity->setSendConfirmationEmail($dto->sendConfirmationEmail);
        }

        if (null !== $dto->solarProcessings) {
            $entity->setSolarProcessings($dto->solarProcessings);
        }

        return $entity;
    }

    protected function getAssociationsMap(): array
    {
        return [
            'newsletterCampaign' => [
                'associationField' => 'newsletterCampaign',
                'targetEntity' => NewsletterCampaign::class,
            ],
        ];
    }
}
