<?php

namespace App\Repository;

use App\Entity\Book;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Book>
 */
class BookRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Book::class);
    }

    //    /**
    //     * @return Book[] Returns an array of Book objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('b')
    //            ->andWhere('b.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('b.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Book
    //    {
    //        return $this->createQueryBuilder('b')
    //            ->andWhere('b.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
    public function search(?string $q, ?string $genre, ?int $ownerId = null): array
    {
        $qb = $this->createQueryBuilder('b')->orderBy('b.createdAt', 'DESC');
        if ($q)     $qb->andWhere('b.title LIKE :q')->setParameter('q', "%$q%");
        if ($genre) $qb->andWhere('b.genre = :g')->setParameter('g', $genre);
        if ($ownerId) $qb->andWhere('b.user = :u')->setParameter('u', $ownerId);
        return $qb->getQuery()->getResult();
    }


}
