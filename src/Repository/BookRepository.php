<?php

namespace App\Repository;

use App\Entity\Book;
use App\Entity\User; // ← l’import correct doit rester ici, en haut
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class BookRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Book::class);
    }

    public function search(?string $q, ?string $genre, ?User $owner = null): array
    {
        $qb = $this->createQueryBuilder('b')
            // si tu veux éviter un souci si createdAt est null, trie par id desc
            ->orderBy('b.id', 'DESC');

        if ($q) {
            $qb->andWhere('b.title LIKE :q')->setParameter('q', "%$q%");
        }
        if ($genre) {
            $qb->andWhere('b.genre = :g')->setParameter('g', $genre);
        }
        if ($owner) {
            $qb->andWhere('b.user = :u')->setParameter('u', $owner);
        }

        return $qb->getQuery()->getResult();
    }
}
