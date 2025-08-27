<?php

namespace App\Repository;

use App\Entity\PaddleWebhookEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PaddleWebhookEvent>
 */
class PaddleWebhookEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PaddleWebhookEvent::class);
    }

    public function findOneByEventId(string $eventId): ?PaddleWebhookEvent
    {
        $result = $this->createQueryBuilder('e')
            ->andWhere('e.eventId = :eventId')
            ->setParameter('eventId', $eventId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $result instanceof PaddleWebhookEvent ? $result : null;
    }
}
