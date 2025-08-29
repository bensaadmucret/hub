<?php

namespace App\Repository;

use App\Entity\PaddleWebhookEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PaddleWebhookEvent>
 *
 * Repository pour gérer les événements webhook Paddle
 * avec support pour les mécanismes de retry
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

    /**
     * Récupère les événements programmés pour retry dont la date de prochaine tentative est passée
     *
     * @param \DateTimeInterface $now Date actuelle pour comparaison
     * @return PaddleWebhookEvent[] Liste des événements à retraiter
     */
    public function findScheduledForRetry(\DateTimeInterface $now): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.status = :status')
            ->andWhere('e.nextRetryAt <= :now')
            ->setParameter('status', 'retry_scheduled')
            ->setParameter('now', $now)
            ->orderBy('e.nextRetryAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les événements en échec qui peuvent être retraités
     *
     * @param int $maxRetries Nombre maximum de tentatives
     * @return PaddleWebhookEvent[] Liste des événements en échec
     */
    public function findRetryableFailedEvents(int $maxRetries = 5): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.status = :status')
            ->andWhere('e.retryCount < :maxRetries')
            ->setParameter('status', 'failed')
            ->setParameter('maxRetries', $maxRetries)
            ->orderBy('e.lastAttemptAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les statistiques des événements webhook
     *
     * @return array Statistiques des événements
     */
    public function getWebhookStats(): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = <<<SQL
            SELECT 
                status, 
                COUNT(*) as count 
            FROM paddle_webhook_event 
            GROUP BY status
        SQL;

        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery();

        return $result->fetchAllAssociative();
    }
}
