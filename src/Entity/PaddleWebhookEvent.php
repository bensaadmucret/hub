<?php

namespace App\Entity;

use App\Repository\PaddleWebhookEventRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PaddleWebhookEventRepository::class)]
#[ORM\Table(name: 'paddle_webhook_event')]
#[ORM\UniqueConstraint(name: 'uniq_paddle_event_id', columns: ['event_id'])]
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

    #[ORM\Column(name: 'status', type: Types::STRING, length: 32)]
    private string $status = 'received';

    public function __construct(string $eventId, ?string $eventType, string $status = 'received')
    {
        $this->eventId = $eventId;
        $this->eventType = $eventType;
        $this->receivedAt = new \DateTimeImmutable('now');
        $this->status = $status;
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
}
