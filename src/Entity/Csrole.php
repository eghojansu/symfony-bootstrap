<?php

namespace App\Entity;

use App\Extension\Auditable\AuditableInterface;
use App\Extension\Auditable\AuditableTrait;
use App\Repository\CsroleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Ignore;

#[ORM\Entity(repositoryClass: CsroleRepository::class)]
class Csrole implements AuditableInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue('NONE')]
    #[ORM\Column(type: 'string', length: 10, name: 'role')]
    private $id;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $description;

    #[ORM\ManyToMany(targetEntity: Csperm::class, inversedBy: 'roles')]
    #[ORM\JoinTable('csrolep')]
    #[ORM\JoinColumn('role', 'role')]
    #[ORM\InverseJoinColumn('perm', 'perm')]
    private $permissions;

    #[ORM\ManyToMany(targetEntity: Csuser::class, mappedBy: 'rbRoles')]
    #[ORM\JoinColumn(name: 'role', referencedColumnName: 'role')]
    #[Ignore]
    private $users;

    use AuditableTrait;

    public function __construct()
    {
        $this->permissions = new ArrayCollection();
        $this->users = new ArrayCollection();
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

    /**
     * @return Collection<int, Csuser>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(Csuser $user): self
    {
        if (!$this->users->contains($user)) {
            $this->users[] = $user;
            $user->addRbRole($this);
        }

        return $this;
    }

    public function removeUser(Csuser $user): self
    {
        if ($this->users->removeElement($user)) {
            $user->removeRbRole($this);
        }

        return $this;
    }
}
