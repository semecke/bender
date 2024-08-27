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

    public function getUserTimeOnVoiceChannel(User $user, VoiceChannel $voiceChannel)
    {
        $qb = $this->createQueryBuilder('s');
        $qb->where('s.user = :user')
            ->andWhere('s.voiceChannel = :voiceChannel')
            ->setParameter('user', $user)
            ->setParameter('voiceChannel', $voiceChannel);

    }

    /**
     * @throws Exception
     */
    public function findAllStatisticsByUser(User $user): mixed
    {
        $qb = $this->createQueryBuilder('s');

        $qb->where('s.user = :user')
            ->andWhere('s.leavedAt is not null')
            ->andWhere('s.joinedAt is not null')
            ->setParameter('user', $user);

        return $qb->getQuery()->getResult();
    }

    public function getUserTimeOnVoiceChannelsForLastMonth(User $user)
    {
        $qb = $this->createQueryBuilder('s');
        $qb->where('s.user = :user')
            ->setParameter('user', $user);
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

        $qb = $qb->select("SUM(TIME_DIFF(s.leavedAt, s.joinedAt, 'second')) as totalSeconds, u.discordId as discordId")
            ->join("s.user", "u")
            ->andWhere('s.leavedAt is not null and s.joinedAt is not null')
            ->groupBy('u.id')
            ->orderBy('totalSeconds', 'DESC')
            ->setMaxResults($countUser);

        if (!empty($topStartDate)) {
            $qb->andWhere('s.joinedAt >= :date')
                ->setParameter('date', $topStartDate);
        }

        return $qb->getQuery()->getResult();
    }

    public function getTopByUser(User $user): mixed
    {
        $qb = $this->createQueryBuilder('s');

        $qb = $qb->select("SUM(TIME_DIFF(s.leavedAt, s.joinedAt, 'second'))")
            ->join("s.user", "u")
            ->andWhere('s.leavedAt is not null and s.joinedAt is not null')
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

        $qb = $qb->select("u.discordId discordId, SUM(TIME_DIFF(LEAST(s1.leavedAt, s2.leavedAt), GREATEST(s1.joinedAt, s2.joinedAt), 'second')) totalSeconds")
            ->innerJoin(VoiceStatistics::class, 's2', 'WITH', 's1.voiceChannel = s2.voiceChannel')
            ->join('s2.user', 'u')
            ->where('s1.leavedAt IS NOT NULL and s2.leavedAt IS NOT NULL')
            ->andWhere('s1.user != s2.user')
            ->andWhere('s1.joinedAt < s2.leavedAt and s2.joinedAt < s1.leavedAt')
            ->andWhere('s1.user = :user')
            ->setParameter('user', $user)
            ->groupBy('s1.user')
            ->addGroupBy('s2.user')
            ->orderBy('totalSeconds', 'DESC')
            ->setMaxResults(3);

        return $qb->getQuery()->getResult();
    }
}
