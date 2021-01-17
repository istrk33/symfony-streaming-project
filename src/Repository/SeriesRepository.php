<?php

namespace App\Repository;

use App\Entity\Series;
use App\Entity\Rating;
use App\Entity\Country;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Series|null find($id, $lockMode = null, $lockVersion = null)
 * @method Series|null findOneBy(array $criteria, array $orderBy = null)
 * @method Series[]    findAll()
 * @method Series[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SeriesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Series::class);
    }

    public function findByCondition($condition, $page, $limit, $attribut, $order)
    {
        return $this->createQueryBuilder('s')
            ->where('s.title LIKE :con')
            ->setParameter('con', '%' . $condition . '%')
            ->orderBy('s.' . $attribut, $order)
            ->setFirstResult(($page * $limit) - $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
    public function findByCurrentSeries($condition, $page, $limit, $order)
    {
        return $this->createQueryBuilder('s')
            ->where('s.yearEnd is NULL')
            ->andWhere('s.title LIKE :con')
            ->setParameter('con', '%' . $condition . '%')
            ->orderBy('s.title', $order)
            ->setFirstResult(($page * $limit) - $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByCountOfCurrentSeries($condition)
    {
        return $this->createQueryBuilder('s')
            ->where('s.yearEnd is NULL')
            ->andWhere('s.title LIKE :con')
            ->setParameter('con', '%' . $condition . '%')
            ->select('count(s.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findByRandom(int $nbRandom)
    {
        $firstResult = rand(0, $this->findByCountOfSeriesWithCondition("") - $nbRandom);
        return $this->createQueryBuilder('s')
            ->setFirstResult($firstResult)
            ->setMaxResults($nbRandom)
            ->getQuery()
            ->getResult();
    }

    public function findByCountOfSeriesWithCondition($condition)
    {
        return $this->createQueryBuilder('s')
            ->where('s.title LIKE :con')
            ->setParameter('con', '%' . $condition . '%')
            ->select('count(s.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findAll_N_LastsUserSeries($userSeries, string $condition, int $maxResults)
    {
        return $this->createQueryBuilder('s')
            ->orderBy('s.yearStart', 'DESC')
            ->where('s.title LIKE :con')
            ->setParameter('con', '%' . $condition . '%')
            ->andWhere('s IN (:series)')
            ->setMaxResults($maxResults)
            ->setParameter('series', $userSeries)
            ->getQuery()
            ->getResult();
    }

    public function findAllSeasonsAndEpisodesById(int $id)
    {
        return $this->createQueryBuilder('s')
            ->where('s.id = (:id)')
            ->setParameter('id', $id)
            ->join('s.seasons', 'i')
            ->addSelect('i')
            ->join('i.episodes', 'e')
            ->addSelect('e')
            ->getQuery()
            ->getResult();
    }

    public function findBySeriesWithConditionByCountryOrGenre($condition, $page, $limit, $case, $value)
    {
        return $this->createQueryBuilder('s')
            ->select('s')
            ->where('s.title LIKE :con')
            ->join('s.' . $case, 'c')
            ->andWhere('c.name LIKE :val')
            ->setParameters(['con' => '%' . $condition . '%', 'val' => $value])
            ->orderBy('s.title', 'ASC')
            ->setFirstResult(($page * $limit) - $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByCountOfSeriesWithConditionByCountryOrGenre($condition, $case,  $value)
    {
        return $this->createQueryBuilder('s')
            ->select('count(s.id)')
            ->join('s.' . $case, 'c')
            ->where('c.name LIKE :val')
            ->andWhere('s.title LIKE :con')
            ->setParameters(['con' => '%' . $condition . '%', 'val' => $value])
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findAllWithRating()
    {
        return $this->createQueryBuilder('s')
            ->select('s')
            ->join('s.comments', 'c')
            ->addSelect('c')
            ->getQuery()
            ->getResult();
    }

    public function findByOfSeriesCountWithRating()
    {
        return $this->createQueryBuilder('s')
            ->select('count(s.id)')
            ->join('s.comments', 'c')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
