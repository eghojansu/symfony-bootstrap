<?php

namespace App\Extension\Auditable;

use App\Entity\Csuser;
use App\Extension\ControllerContext;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('doctrine.event_listener', array(
    'event' => Events::onFlush,
))]
class Listener
{
    public function __construct(
        private ControllerContext $context,
    ) {}

    public function onFlush(OnFlushEventArgs $args)
    {
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();
        $now = new \DateTime();
        $user = $this->context->user();

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if ($entity instanceof AuditableInterface && $entity->isAuditable()) {
                $meta = $em->getClassMetadata(get_class($entity));

                $this->touch($entity, $user, $now);
                $uow->recomputeSingleEntityChangeSet($meta, $entity);
            }
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if ($entity instanceof AuditableInterface && $entity->isAuditable()) {
                $meta = $em->getClassMetadata(get_class($entity));

                $this->touchUpdate($entity, $user, $now);
                $uow->recomputeSingleEntityChangeSet($meta, $entity);
            }
        }

        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            if (
                $entity instanceof AuditableInterface
                && $entity->isAuditable()
                && !$entity->getDeletedAt()
            ) {
                $em->persist($entity);

                $updates = $this->touchDelete($entity, $user, $now);

                $uow->scheduleExtraUpdate($entity, $updates);
            }
        }
    }

    private function touch(
        AuditableInterface $entity,
        Csuser|null $user,
        \DateTime $ts,
    ): void {
        if (null === $entity->getCreatedAt()) {
            $entity->setCreatedAt($ts);
        }

        if (null === $entity->getCreatedBy() && $user) {
            $entity->setCreatedBy($user);
        }

        $this->touchUpdate($entity, $user, $ts, false);
    }

    private function touchUpdate(
        AuditableInterface $entity,
        Csuser|null $user,
        \DateTime $ts,
        bool $editing = true,
    ): void {
        if ($editing || null === $entity->getUpdatedAt()) {
            $entity->setUpdatedAt($ts);
        }

        if (
            ($editing || null === $entity->getUpdatedBy())
            && $user
        ) {
            $entity->setUpdatedBy($user);
        }
    }

    private function touchDelete(
        AuditableInterface $entity,
        Csuser|null $user,
        \DateTime $now,
    ): array {
        $updates = array(
            'deletedAt' => array($entity->getDeletedAt(), $now),
        );

        $entity->setDeletedAt($now);

        if ($user) {
            $updates['deletedBy'] = array($entity->getDeletedBy(), $user);

            $entity->setDeletedBy($user);
        }

        return $updates;
    }
}