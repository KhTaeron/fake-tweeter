<?php

namespace App\Repository;

use App\Entity\Tweet;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Tweet>
 */
class TweetRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tweet::class);
    }

    public function fetchAllOrdered(): array {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.tweeter', 'u')
            ->addSelect('u')           
            ->orderBy('t.publicationDate', 'DESC')
            ->getQuery()
            ->getResult();  
            
    }

    public function fetchOneById(int $id): ?Tweet {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.tweeter', 'u')
            ->addSelect('u')
            ->andWhere('t.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();   
    }


    public function searchByKeyword(string $keyword): array
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.tweeter', 'u')
            ->addSelect('u') 
            ->where('t.content LIKE :kw')
            ->setParameter('kw', '%' . $keyword . '%')
            ->orderBy('t.publicationDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

//    /**
//     * @return Tweet[] Returns an array of Tweet objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('t.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Tweet
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
