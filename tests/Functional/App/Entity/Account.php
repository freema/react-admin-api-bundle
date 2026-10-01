<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Tests\Functional\App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Freema\ReactAdminApiBundle\Interface\AdminEntityInterface;
use Freema\ReactAdminApiBundle\Interface\RelatedEntityInterface;
use Freema\ReactAdminApiBundle\Tests\Functional\App\Repository\AccountRepository;

#[ORM\Entity(repositoryClass: AccountRepository::class)]
#[ORM\Table(name: 'accounts')]
class Account implements AdminEntityInterface, RelatedEntityInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public ?int $id = null;

    #[ORM\Column(type: 'string')]
    public string $name = '';

    #[ORM\Column(type: 'string')]
    public string $email = '';

    /** Never part of the API: AccountDto has no such property. */
    #[ORM\Column(type: 'string')]
    public string $password = '';

    public function getAlias(): string
    {
        return 'account';
    }
}
