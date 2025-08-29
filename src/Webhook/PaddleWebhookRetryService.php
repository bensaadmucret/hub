<?php

namespace App\Webhook;

use App\Entity\PaddleWebhookEvent;
use App\Repository\PaddleWebhookEventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Service responsable de la gestion des retries pour les webhooks Paddle
 */
class PaddleWebhookRetryService
{
    private const MAX_RETRIES = 5;

    /**
     * Délais entre les tentatives en secondes (exponentiel)
     * 5min, 15min, 45min, 2h, 6h
     */
    private const RETRY_DELAYS = [300, 900, 2700, 7200, 21600];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PaddleWebhookEventRepository $webhookEventRepository,
        private readonly PaddleEventRouter $eventRouter,
        private readonly LoggerInterface $logger,
        #[Autowire('%kernel.environment%')]
        private readonly string $environment,
    ) {
    }

    /**
     * Marque un événement comme échoué et programme une nouvelle tentative si possible
     */
    public function markAsFailed(PaddleWebhookEvent $event, \Throwable $exception): void
    {
        $event->updateLastAttempt();
        $event->incrementRetryCount();
        $event->setErrorMessage($exception->getMessage());

        $retryCount = $event->getRetryCount();

        if ($retryCount < self::MAX_RETRIES) {
            $delayIndex = min($retryCount - 1, count(self::RETRY_DELAYS) - 1);
            $delay = self::RETRY_DELAYS[$delayIndex];

            $event->scheduleRetry($delay);
            $this->logger->info(sprintf(
                'Webhook event %s failed, scheduled for retry %d/%d in %d seconds',
                $event->getEventId(),
                $retryCount,
                self::MAX_RETRIES,
                $delay
            ));
        } else {
            $event->setStatus('failed');
            $this->logger->error(sprintf(
                'Webhook event %s failed permanently after %d retries: %s',
                $event->getEventId(),
                $retryCount,
                $exception->getMessage()
            ));
        }

        $this->entityManager->flush();
    }

    /**
     * Traite un événement webhook
     */
    public function processEvent(PaddleWebhookEvent $event): bool
    {
        try {
            $event->setStatus('processing');
            $event->updateLastAttempt();
            $this->entityManager->flush();

            $payload = $event->getDecodedPayload();
            if ($payload === null) {
                throw new \RuntimeException('Cannot decode webhook payload');
            }

            $this->eventRouter->route($event->getEventType(), $payload);

            $event->setStatus('processed');
            $this->entityManager->flush();

            $this->logger->info(sprintf(
                'Successfully processed webhook event %s (type: %s)',
                $event->getEventId(),
                $event->getEventType()
            ));

            return true;
        } catch (\Throwable $e) {
            $this->markAsFailed($event, $e);
            return false;
        }
    }

    /**
     * Récupère et traite les événements programmés pour retry
     */
    public function processScheduledRetries(): int
    {
        $now = new \DateTimeImmutable();
        $events = $this->webhookEventRepository->findScheduledForRetry($now);

        $processedCount = 0;

        foreach ($events as $event) {
            if ($this->processEvent($event)) {
                $processedCount++;
            }
        }

        return $processedCount;
    }
}
