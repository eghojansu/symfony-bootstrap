<?php

namespace App\Extension\Auditable;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;

class Filter extends SQLFilter
{
    const NAME = 'auditable';

    /**
     * @return string
     */
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias)
    {
        if (!$targetEntity->reflClass->implementsInterface(AuditableInterface::class)) {
            return '';
        }

        return $targetTableAlias . '.' . $this->getConnection()->quoteIdentifier(
            $targetEntity->getColumnName('deletedAt')
        ) . ' IS NULL';
    }
}