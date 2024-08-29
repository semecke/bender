<?php

namespace App\Repository;

use App\Entity\ActivityStatistics;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;

class ActivityStatisticsRepository extends EntityRepository
{
    /**
     * @throws NonUniqueResultException
     */
    public function findLastOneJoinedStatistics(User $user): ?ActivityStatistics
    {
        $qb = $this->createQueryBuilder('s');
        $qb->andWhere('s.user = :user')
            ->andWhere('s.leavedAt is null')
            ->setParameter('user', $user)
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function getPersonalStatisticsForUser(User $user): mixed
    {
        $qb = $this->createQueryBuilder('s');

        $qb = $qb->select("SUM(TIME_DIFF(IFNULL(s.leavedAt, UTC_TIMESTAMP()), s.joinedAt, 'second')) totalSeconds, a.name as activityName")
            ->join("s.user", "u")
            ->join("s.activity", "a")
            ->andWhere('u.id = :user')
            ->setParameter('user', $user)
            ->addGroupBy('s.activity')
            ->addOrderBy('totalSeconds', 'DESC')
            ->setMaxResults(3);

        return $qb->getQuery()->getResult();
    }
}
