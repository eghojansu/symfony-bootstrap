<?php

namespace App\Entity;

use App\Extension\Auditable\AuditableInterface;
use App\Extension\Auditable\AuditableTrait;
use App\Repository\CspermRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Ignore;

#[ORM\Entity(repositoryClass: CspermRepository::class)]
class Csperm implements AuditableInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue('NONE')]
    #[ORM\Column(type: 'string', length: 10, name: 'perm')]
    private $id;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $description;

    #[ORM\ManyToMany(targetEntity: Csmod::class, mappedBy: 'permissions')]
    #[Ignore]
    private $modules;

    #[ORM\ManyToMany(targetEntity: Csrole::class, mappedBy: 'permissions')]
    #[Ignore]
    private $roles;

    use AuditableTrait;

    public function __construct()
    {
        $this->modules = new ArrayCollection();
        $this->roles = new ArrayCollection();
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

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection<int, Csmod>
     */
    public function getModules(): Collection
    {
        return $this->modules;
    }

    public function addModule(Csmod $module): self
    {
        if (!$this->modules->contains($module)) {
            $this->modules[] = $module;
            $module->addPermission($this);
        }

        return $this;
    }

    public function removeModule(Csmod $module): self
    {
        if ($this->modules->removeElement($module)) {
            $module->removePermission($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Csrole>
     */
    public function getRoles(): Collection
    {
        return $this->roles;
    }

    public function addRole(Csrole $role): self
    {
        if (!$this->roles->contains($role)) {
            $this->roles[] = $role;
            $role->addPermission($this);
        }

        return $this;
    }

    public function removeRole(Csrole $role): self
    {
        if ($this->roles->removeElement($role)) {
            $role->removePermission($this);
        }

        return $this;
    }
}
