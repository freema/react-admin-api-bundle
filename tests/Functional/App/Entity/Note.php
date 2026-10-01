<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Tests\Functional\App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Freema\ReactAdminApiBundle\Interface\AdminEntityInterface;
use Freema\ReactAdminApiBundle\Tests\Functional\App\Repository\NoteRepository;

#[ORM\Entity(repositoryClass: NoteRepository::class)]
#[ORM\Table(name: 'notes')]
class Note implements AdminEntityInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public ?int $id = null;

    #[ORM\Column(type: 'string')]
    public string $title = '';

    /** Internal, not exposed by NoteDto. */
    #[ORM\Column(type: 'string')]
    public string $secret = '';

    #[ORM\ManyToOne(targetEntity: Account::class)]
    public ?Account $account = null;
}
