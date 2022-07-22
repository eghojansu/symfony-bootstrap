<?php

namespace App\Repository;

use App\Entity\Csmenu;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Csmenu>
 *
 * @method Csmenu|null find($id, $lockMode = null, $lockVersion = null)
 * @method Csmenu|null findOneBy(array $criteria, array $orderBy = null)
 * @method Csmenu[]    findAll()
 * @method Csmenu[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CsmenuRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Csmenu::class);
    }

    public function add(Csmenu $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Csmenu $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return Csmenu[]
     */
    public function getMenu(): array
    {
        $qb = $this->createQueryBuilder('a');
        $qb->where(
            $qb->expr()->andX(
                $qb->expr()->orX(
                    $qb->expr()->isNull('a.hidden'),
                    $qb->expr()->eq('a.hidden', ':hidden')
                ),
                $qb->expr()->eq('a.active', ':active')
            )
        );
        $qb->setParameters(array(
            'active' => true,
            'hidden' => false,
        ));

        return $qb->getQuery()->getResult();
    }

    public function findMenu(string $path): ?Csmenu
    {
        $qb = $this->createQueryBuilder('a');
        $qb->where(
            $qb->expr()->andX(
                $qb->expr()->eq('a.active', ':active'),
                $qb->expr()->eq('a.path', ':path'),
            )
        );
        $qb->setParameters(array(
            'active' => true,
        ) + compact('path'));

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function getNextChildPriority(Csmenu $parent): int
    {
        $qb = $this->createQueryBuilder('a');
        $qb->where('a.parent = :parent');
        $qb->orderBy('a.priority', 'DESC');
        $qb->setMaxResults(1);
        $qb->setParameters(compact('parent'));

        $found = $qb->getQuery()->getOneOrNullResult();

        return ($found?->getPriority() ?? 0) + 1;
    }

    public function removeSorted(Csmenu $menu): void
    {
        $this->doOrder($menu->getParent()->getChildren(), $menu);

        $this->getEntityManager()->remove($menu);
        $this->getEntityManager()->flush();
    }

    public function reSort(Csmenu $menu, string $direction): void
    {
        $children = $menu->getParent()->getChildren()->toArray();
        $pos = array_search($menu, $children, true);
        $move = $pos - ('down' === $direction ? -1 : 1);

        $children[$pos] = $children[$move];
        $children[$move] = $menu;

        $this->doOrder($children);

        $this->getEntityManager()->flush();
    }

    private function doOrder(iterable $items, Csmenu $ignore = null): void
    {
        $priority = 0;

        foreach ($items as $item) {
            if (!$ignore || $item !== $ignore) {
                $item->setPriority(++$priority);
            }
        }
    }
}
