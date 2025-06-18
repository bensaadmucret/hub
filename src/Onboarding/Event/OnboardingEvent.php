<?php

namespace App\Onboarding\Event;

use App\Core\Entity\User;
use Symfony\Component\Workflow\Event\Event;

class OnboardingEvent extends Event
{
    public const NAME = 'onboarding';

    private User $user;
    private string $fromState;
    private string $toState;
    private array $context;

    public function __construct(User $user, string $fromState, string $toState, array $context = [])
    {
        $this->user = $user;
        $this->fromState = $fromState;
        $this->toState = $toState;
        $this->context = $context;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getFromState(): string
    {
        return $this->fromState;
    }

    public function getToState(): string
    {
        return $this->toState;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function setContext(array $context): self
    {
        $this->context = $context;
        return $this;
    }
}
