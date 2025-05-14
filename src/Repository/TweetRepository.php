<?php

namespace App\Repository;

use App\Entity\Tweet;
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

    public function fetchAllOrdered(): array
    {
        return $this->createQueryBuilder('t')
            ->select('t.id',
                     't.content',
                     't.publicationDate',
                     'IDENTITY(t.tweeter) AS tweeterId')
            ->orderBy('t.publicationDate', 'DESC')
            ->getQuery()
            ->getArrayResult();
    }

    public function fetchOneById(int $id): ?array
    {
        return $this->createQueryBuilder('t')
            ->select('t.id',
                     't.content',
                     't.publicationDate',
                     'IDENTITY(t.tweeter) AS tweeterId')
            ->andWhere('t.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();   
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
