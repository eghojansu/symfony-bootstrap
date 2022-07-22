<?php

namespace App\Extension\Auditable;

use App\Entity\Csuser;
use Doctrine\ORM\Mapping as ORM;

trait AuditableTrait
{
    #[ORM\Column(name: 'creat', type: 'datetime', nullable: true)]
    private $createdAt;

    #[ORM\Column(name: 'updat', type: 'datetime', nullable: true)]
    private $updatedAt;

    #[ORM\Column(name: 'delat', type: 'datetime', nullable: true)]
    private $deletedAt;

    #[ORM\ManyToOne(Csuser::class)]
    #[ORM\JoinColumn(name: 'creby', referencedColumnName: 'userid')]
    private $createdBy;

    #[ORM\ManyToOne(Csuser::class)]
    #[ORM\JoinColumn(name: 'updby', referencedColumnName: 'userid')]
    private $updatedBy;

    #[ORM\ManyToOne(Csuser::class)]
    #[ORM\JoinColumn(name: 'delby', referencedColumnName: 'userid')]
    private $deletedBy;

    private $auditable = true;

    public function getCreatedAt(): \DateTime|null
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime|null $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): \DateTime|null
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTime|null $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getDeletedAt(): \DateTime|null
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(\DateTime|null $deletedAt): static
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    public function getCreatedBy(): Csuser|null
    {
        return $this->createdBy;
    }

    public function setCreatedBy(Csuser|null $createdBy): static
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getUpdatedBy(): Csuser|null
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(Csuser|null $updatedBy): static
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }

    public function getDeletedBy(): Csuser|null
    {
        return $this->deletedBy;
    }

    public function setDeletedBy(Csuser|null $deletedBy): static
    {
        $this->deletedBy = $deletedBy;

        return $this;
    }

    public function isAuditable(): bool
    {
        return $this->auditable;
    }

    public function setAuditable(bool $auditable): static
    {
        $this->auditable = $auditable;

        return $this;
    }
}