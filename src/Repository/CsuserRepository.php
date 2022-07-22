<?php

namespace App\Repository;

use App\Entity\Csuser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Csuser>
 *
 * @method Csuser|null find($id, $lockMode = null, $lockVersion = null)
 * @method Csuser|null findOneBy(array $criteria, array $orderBy = null)
 * @method Csuser[]    findAll()
 * @method Csuser[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CsuserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Csuser::class);
    }

    public function add(Csuser $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Csuser $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findUser(string $account): ?Csuser
    {
        $qb = $this->createQueryBuilder('u');
        $find = filter_var($account, FILTER_VALIDATE_EMAIL) ? 'u.email' : 'u.id';

        return $qb->where($qb->expr()->eq($find, '?1'))
            ->setParameter(1, $account)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

//    /**
//     * @return Csuser[] Returns an array of Csuser objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('c.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Csuser
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
