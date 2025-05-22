<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Dev\Entity;

use Freema\ReactAdminApiBundle\Interface\AdminEntityInterface;
use Freema\ReactAdminApiBundle\Interface\RelatedEntityInterface;

class User implements AdminEntityInterface, RelatedEntityInterface
{
    private ?int $id = null;
    private string $name = '';
    private string $email = '';
    private array $roles = [];
    private ?string $createdAt = null;

    public function __construct()
    {
        $this->createdAt = date('Y-m-d H:i:s');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @return array<string>
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * @param array<string> $roles
     */
    public function setRoles(array $roles): self
    {
        $this->roles = $roles;
        return $this;
    }
    
    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function getAlias(): string
    {
        return 'user';
    }
}