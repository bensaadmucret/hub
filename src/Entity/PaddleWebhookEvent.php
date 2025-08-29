<?php

namespace App\Entity;

use App\Repository\PaddleWebhookEventRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entité représentant un événement webhook Paddle reçu
 * Supporte le mécanisme de retry avec statut, compteur et payload
 */
#[ORM\Entity(repositoryClass: PaddleWebhookEventRepository::class)]
#[ORM\Table(name: 'paddle_webhook_event')]
#[ORM\UniqueConstraint(name: 'uniq_paddle_event_id', columns: ['event_id'])]
#[ORM\Index(columns: ['status', 'retry_count'], name: 'idx_paddle_event_retry')]
class PaddleWebhookEvent
{
    /**
     * @phpstan-var int|null set by Doctrine
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    // @phpstan-ignore-next-line Doctrine hydrates the id at runtime
    private ?int $id = null;

    #[ORM\Column(name: 'event_id', type: Types::STRING, length: 191)]
    private string $eventId;

    #[ORM\Column(name: 'event_type', type: Types::STRING, length: 191, nullable: true)]
    private ?string $eventType = null;

    #[ORM\Column(name: 'received_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $receivedAt;

    /**
     * Status de l'événement:
     * - received: événement reçu mais pas encore traité
     * - processing: en cours de traitement
     * - processed: traité avec succès
     * - failed: échec de traitement
     * - retry_scheduled: programmé pour une nouvelle tentative
     */
    #[ORM\Column(name: 'status', type: Types::STRING, length: 32)]
    private string $status = 'received';

    /**
     * Nombre de tentatives de traitement effectuées
     */
    #[ORM\Column(name: 'retry_count', type: Types::INTEGER)]
    private int $retryCount = 0;

    /**
     * Date de la dernière tentative de traitement
     */
    #[ORM\Column(name: 'last_attempt_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $lastAttemptAt = null;

    /**
     * Date de la prochaine tentative programmée
     */
    #[ORM\Column(name: 'next_retry_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $nextRetryAt = null;

    /**
     * Message d'erreur en cas d'échec
     */
    #[ORM\Column(name: 'error_message', type: Types::TEXT, nullable: true)]
    private ?string $errorMessage = null;

    /**
     * Payload complet de l'événement au format JSON
     */
    #[ORM\Column(name: 'payload', type: Types::TEXT, nullable: true)]
    private ?string $payload = null;

    public function __construct(string $eventId, ?string $eventType, string $status = 'received', ?string $payload = null)
    {
        $this->eventId = $eventId;
        $this->eventType = $eventType;
        $this->receivedAt = new \DateTimeImmutable('now');
        $this->status = $status;
        $this->payload = $payload;
        $this->retryCount = 0;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEventId(): string
    {
        return $this->eventId;
    }

    public function getEventType(): ?string
    {
        return $this->eventType;
    }

    public function setEventType(?string $eventType): void
    {
        $this->eventType = $eventType;
    }

    public function getReceivedAt(): \DateTimeImmutable
    {
        return $this->receivedAt;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getRetryCount(): int
    {
        return $this->retryCount;
    }

    public function incrementRetryCount(): void
    {
        $this->retryCount++;
    }

    public function getLastAttemptAt(): ?\DateTimeImmutable
    {
        return $this->lastAttemptAt;
    }

    public function setLastAttemptAt(\DateTimeImmutable $lastAttemptAt): void
    {
        $this->lastAttemptAt = $lastAttemptAt;
    }

    public function updateLastAttempt(): void
    {
        $this->lastAttemptAt = new \DateTimeImmutable('now');
    }

    public function getNextRetryAt(): ?\DateTimeImmutable
    {
        return $this->nextRetryAt;
    }

    public function setNextRetryAt(?\DateTimeImmutable $nextRetryAt): void
    {
        $this->nextRetryAt = $nextRetryAt;
    }

    public function scheduleRetry(int $delaySeconds = 300): void
    {
        $this->status = 'retry_scheduled';
        $this->nextRetryAt = new \DateTimeImmutable('+' . $delaySeconds . ' seconds');
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): void
    {
        $this->errorMessage = $errorMessage;
    }

    public function getPayload(): ?string
    {
        return $this->payload;
    }

    public function setPayload(?string $payload): void
    {
        $this->payload = $payload;
    }

    /**
     * Décode le payload JSON en tableau associatif
     *
     * @return array<string, mixed>|null
     */
    public function getDecodedPayload(): ?array
    {
        if ($this->payload === null) {
            return null;
        }

        try {
            return json_decode($this->payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return null;
        }
    }

    /**
     * Vérifie si l'événement peut être retraité
     */
    public function isRetryable(int $maxRetries = 5): bool
    {
        return $this->status === 'failed' && $this->retryCount < $maxRetries;
    }
}
