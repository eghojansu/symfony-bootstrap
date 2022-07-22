<?php

namespace App\Repository;

use App\Entity\Cshist;
use App\Entity\Csuser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Cshist>
 *
 * @method Cshist|null find($id, $lockMode = null, $lockVersion = null)
 * @method Cshist|null findOneBy(array $criteria, array $orderBy = null)
 * @method Cshist[]    findAll()
 * @method Cshist[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CshistRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cshist::class);
    }

    public function add(Cshist $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Cshist $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return Cshist[] Returns an array of Cshist objects
     */
    public function getUserActivities(Csuser $user): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.user = :user')
            ->setParameter('user', $user)
            ->orderBy('a.recordDate', 'DESC')
            ->setMaxResults(15)
            ->getQuery()
            ->getResult()
        ;
    }

//    public function findOneBySomeField($value): ?Cshist
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
