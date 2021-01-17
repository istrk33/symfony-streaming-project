<?php

namespace App\Repository;


use App\Entity\Rating;
use App\Entity\Series;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Rating|null find($id, $lockMode = null, $lockVersion = null)
 * @method Rating|null findOneBy(array $criteria, array $orderBy = null)
 * @method Rating[]    findAll()
 * @method Rating[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RatingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Rating::class);
    }

    public function findAll_N_Comments(int $maxResults)
    {
        return $this->createQueryBuilder('r')
            ->select('r, s')
            ->join('r.series', 's')
            ->orderBy('s.title', 'ASC')
            ->setMaxResults($maxResults)
            ->getQuery()
            ->getResult();
    }

    public function findAllMyComments(User $user)
    {
        return $this->createQueryBuilder('r')
            ->select('r, s')
            ->join('r.series', 's')
            ->where('r.user = ?1')
            ->orderBy('s.title', 'ASC')
            ->setParameter(1, $user)
            ->getQuery()
            ->getResult();
    }
    public function findAll_N_MyComments(User $user, int $maxResults)
    {
        return $this->createQueryBuilder('r')
            ->select('r, s')
            ->join('r.series', 's')
            ->where('r.user = ?1')
            ->orderBy('s.title', 'ASC')
            ->setMaxResults($maxResults)
            ->setParameter(1, $user)
            ->getQuery()
            ->getResult();
    }
    public function findAll_N_LastsCommentsByUser(User $user, int $maxResults)
    {
        return $this->createQueryBuilder('r')
            ->select('r, s')
            ->join('r.series', 's')
            ->where('r.user = (:theUser)')
            ->orderBy('r.date', 'DESC')
            ->setMaxResults($maxResults)
            ->setParameter('theUser', $user)
            ->getQuery()
            ->getResult();
    }

    public function findByCountOfAllComments()
    {
        return $this->createQueryBuilder('r')
            ->select('count(r.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findByCountOfUserComments(User $user)
    {
        return $this->createQueryBuilder('r')
            ->select('count(r.id)')
            ->where('r.user = ?1')
            ->setParameter(1, $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findByCountOfUserCommentsPerSerie(User $user, int $serieId)
    {
        return $this->createQueryBuilder('r')
            ->select('count(r.id)')
            ->join('r.series', 's')
            ->where('s.id = ?1')
            ->andWhere('r.user = ?2')
            ->setParameter(1, $serieId)
            ->setParameter(2, $user)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
