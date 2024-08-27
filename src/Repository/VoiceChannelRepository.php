<?php

namespace App\Repository;

use App\Entity\VoiceChannel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<VoiceChannel>
 *
 * @method VoiceChannel|null find($id, $lockMode = null, $lockVersion = null)
 * @method VoiceChannel|null findOneBy(array $criteria, array $orderBy = null)
 * @method VoiceChannel[]    findAll()
 * @method VoiceChannel[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VoiceChannelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VoiceChannel::class);
    }

//    /**
//     * @return VoiceChannel[] Returns an array of VoiceChannel objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('v')
//            ->andWhere('v.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('v.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?VoiceChannel
//    {
//        return $this->createQueryBuilder('v')
//            ->andWhere('v.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
