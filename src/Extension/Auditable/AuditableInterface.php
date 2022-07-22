<?php

namespace App\Extension\Auditable;

use App\Entity\Csuser;

interface AuditableInterface
{
    public function getCreatedAt(): \DateTime|null;
    public function setCreatedAt(\DateTime|null $createdAt): static;

    public function getUpdatedAt(): \DateTime|null;
    public function setUpdatedAt(\DateTime|null $updatedAt): static;

    public function getDeletedAt(): \DateTime|null;
    public function setDeletedAt(\DateTime|null $deletedAt): static;

    public function getCreatedBy(): Csuser|null;
    public function setCreatedBy(Csuser|null $createdBy): static;

    public function getUpdatedBy(): Csuser|null;
    public function setUpdatedBy(Csuser|null $updatedBy): static;

    public function getDeletedBy(): Csuser|null;
    public function setDeletedBy(Csuser|null $deletedBy): static;

    public function isAuditable(): bool;
    public function setAuditable(bool $auditable): static;
}