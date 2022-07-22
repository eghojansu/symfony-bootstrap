<?php

namespace App\Entity;

use App\Extension\Auditable\AuditableInterface;
use App\Extension\Auditable\AuditableTrait;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\CsmodRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity(repositoryClass: CsmodRepository::class)]
class Csmod implements AuditableInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue('NONE')]
    #[ORM\Column(type: 'string', length: 10, name: 'modid')]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    private $description;

    #[ORM\ManyToMany(targetEntity: Csperm::class, inversedBy: 'modules')]
    #[ORM\JoinTable('csmodp')]
    #[ORM\JoinColumn('modid', 'modid')]
    #[ORM\InverseJoinColumn('perm', 'perm')]
    private $permissions;

    use AuditableTrait;

    public function __construct()
    {
        $this->permissions = new ArrayCollection();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection<int, Csperm>
     */
    public function getPermissions(): Collection
    {
        return $this->permissions;
    }

    public function addPermission(Csperm $permission): self
    {
        if (!$this->permissions->contains($permission)) {
            $this->permissions[] = $permission;
        }

        return $this;
    }

    public function removePermission(Csperm $permission): self
    {
        $this->permissions->removeElement($permission);

        return $this;
    }
}
