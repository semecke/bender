<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\VoiceChannel;
use App\Entity\VoiceStatistics;
use DateTime;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;

class VoiceStatisticsRepository extends EntityRepository
{
    /**
     * @throws NonUniqueResultException
     */
    public function findLastOneJoinedStatistics(User $user, VoiceChannel $voiceChannel): ?VoiceStatistics
    {
        $qb = $this->createQueryBuilder('s');
        $qb->andWhere('s.user = :user')
            ->andWhere('s.voiceChannel = :voiceChannel')
            ->andWhere('s.leavedAt is null')
            ->setParameter('user', $user)
            ->setParameter('voiceChannel', $voiceChannel)
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findActiveStatisticsByUser(User $user): array
    {
        $qb = $this->createQueryBuilder('s');

        $qb->where('s.user = :user')
            ->andWhere('s.leavedAt is null')
            ->setParameter('user', $user);

        return $qb->getQuery()->getResult();
    }

    public function findActiveStatisticsByChannel(VoiceChannel $voiceChannel): array
    {
        $qb = $this->createQueryBuilder('s');

        $qb->where('s.voiceChannel = :voice_channel')
            ->andWhere('s.leavedAt is null')
            ->setParameter('voice_channel', $voiceChannel);

        return $qb->getQuery()->getResult();
    }

    public function getTopForPeriod(int $countUser = 10, ?Datetime $topStartDate = null): mixed
    {
        $qb = $this->createQueryBuilder('s');

        $qb = $qb->select("SUM(TIME_DIFF(IFNULL(s.leavedAt, utc_timestamp()), GREATEST(s.joinedAt, :date), 'second')) as totalSeconds, u.discordId as discordId")
            ->join("s.user", "u")
            ->groupBy('u.id')
            ->orderBy('totalSeconds', 'DESC')
            ->setMaxResults($countUser);

        if (!empty($topStartDate)) {
            $qb->andWhere('s.joinedAt >= :date')
                ->setParameter('date', $topStartDate);
        }

        return $qb->getQuery()->getResult();
    }

    public function getPersonalStatisticsForUser(User $user): mixed
    {
        $qb = $this->createQueryBuilder('s');

        $qb = $qb->select("SUM(TIME_DIFF(IFNULL(s.leavedAt, utc_timestamp()), s.joinedAt, 'second'))")
            ->join("s.user", "u")
            ->andWhere('u.id = :user')
            ->setParameter('user', $user)
            ->groupBy('u.id')
            ->setMaxResults(1);
        try {
            return $qb->getQuery()->getSingleScalarResult() ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    public function getFriendsTimeByUser(User $user): mixed
    {
        $qb = $this->createQueryBuilder('s1');

        $qb = $qb->select("u.discordId discordId, SUM(TIME_DIFF(LEAST(IFNULL(s1.leavedAt, utc_timestamp()), IFNULL(s2.leavedAt, utc_timestamp())), GREATEST(s1.joinedAt, s2.joinedAt), 'second')) totalSeconds")
            ->innerJoin(VoiceStatistics::class, 's2', 'WITH', 's1.voiceChannel = s2.voiceChannel')
            ->join('s2.user', 'u')
            ->andWhere('s1.user != s2.user')
            ->andWhere('s1.joinedAt < IFNULL(s2.leavedAt, utc_timestamp()) and s2.joinedAt < IFNULL(s1.leavedAt, utc_timestamp())')
            ->andWhere('s1.user = :user')
            ->setParameter('user', $user)
            ->groupBy('s1.user')
            ->addGroupBy('s2.user')
            ->orderBy('totalSeconds', 'DESC')
            ->setMaxResults(3);

        return $qb->getQuery()->getResult();
    }
}
