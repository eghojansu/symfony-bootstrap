<?php

namespace App\Repository;

use App\Entity\Csrole;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Csrole>
 *
 * @method Csrole|null find($id, $lockMode = null, $lockVersion = null)
 * @method Csrole|null findOneBy(array $criteria, array $orderBy = null)
 * @method Csrole[]    findAll()
 * @method Csrole[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CsroleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Csrole::class);
    }

    public function add(Csrole $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Csrole $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    public function findOneBySomeField($value): ?Csrole
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
