<?php

namespace DenikProfile\Entity;

use DateTimeImmutable;
use DenikProfile\Entity\Attributes\IdAttributeBigIntTrait;
use DenikProfile\Newsletter\Promo\Admin\Interface\AdminEntityInterface;
use DenikProfile\Newsletter\Promo\Admin\Interface\RelatedEntityInterface;
use DenikProfile\Newsletter\Promo\Admin\Repository\PromoCreativeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'promo_creative')]
#[ORM\Entity(repositoryClass: PromoCreativeRepository::class)]
class PromoCreative implements AdminEntityInterface, RelatedEntityInterface
{
    use IdAttributeBigIntTrait;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $updatedAt;

    /**
     * @var Collection|PromoShowUp[]
     */
    #[ORM\OneToMany(mappedBy: 'promoCreative', targetEntity: 'DenikProfile\Entity\PromoShowUp', fetch: 'EAGER', orphanRemoval: true)]
    #[ORM\OrderBy(['priority' => 'DESC'])]
    private Collection|array $promoShowUps;

    public function __construct(
        #[ORM\Column(type: 'string', length: 256, nullable: false)]
        private string $name,
        #[ORM\Column(type: 'string', length: 2048, nullable: false)]
        private string $title,
        #[ORM\Column(type: 'text', nullable: false)]
        private string $link,
        #[ORM\Column(type: 'text', nullable: false)]
        private string $imageUrl,
        #[ORM\Column(type: 'text', nullable: true)]
        private ?string $mobileImageUrl = null,
        #[ORM\Column(type: 'string', length: 2048, nullable: true)]
        private ?string $category = null,
        #[ORM\Column(type: 'text', nullable: true)]
        private ?string $description = null,
        #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => 0])]
        private bool $disabled = false,
    ) {
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
        $this->promoShowUps = new ArrayCollection();
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getLink(): string
    {
        return $this->link;
    }

    public function getImageUrl(): string
    {
        return $this->imageUrl;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function setLink(string $link): void
    {
        $this->link = $link;
    }

    public function setImageUrl(string $imageUrl): void
    {
        $this->imageUrl = $imageUrl;
    }

    public function setCategory(?string $category): void
    {
        $this->category = $category;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function setDisabled(bool $disabled): void
    {
        $this->disabled = $disabled;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTimeImmutable $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function getMobileImageUrl(): ?string
    {
        return $this->mobileImageUrl;
    }

    public function setMobileImageUrl(?string $mobileImageUrl): void
    {
        $this->mobileImageUrl = $mobileImageUrl;
    }

    public function getAlias(): string
    {
        return 'promoCreative';
    }
}
