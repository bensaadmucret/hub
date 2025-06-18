<?php

namespace App\Onboarding\EventSubscriber;

use App\Core\Entity\User;
use App\Onboarding\Event\OnboardingEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\Event\GuardEvent;

class WorkflowSubscriber implements EventSubscriberInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Événements de transition
            'workflow.onboarding.transition.verify_email' => 'onVerifyEmail',
            'workflow.onboarding.transition.complete_profile' => 'onCompleteProfile',
            'workflow.onboarding.transition.select_subscription' => 'onSelectSubscription',
            'workflow.onboarding.transition.process_payment' => 'onProcessPayment',
            'workflow.onboarding.transition.complete_onboarding' => 'onCompleteOnboarding',

            // Événements de garde (vérification des conditions avant transition)
            'workflow.onboarding.guard.verify_email' => 'guardOnboarding',
            'workflow.onboarding.guard.complete_profile' => 'guardOnboarding',
            'workflow.onboarding.guard.select_subscription' => 'guardOnboarding',
            'workflow.onboarding.guard.process_payment' => 'guardOnboarding',
            'workflow.onboarding.guard.complete_onboarding' => 'guardOnboarding',

            // Événements personnalisés
            OnboardingEvent::NAME => 'onOnboardingEvent',
        ];
    }

    /**
     * Gestionnaire pour la vérification d'email
     */
    public function onVerifyEmail(Event $event): void
    {
        /** @var User $user */
        $user = $event->getSubject();

        $this->logger->info(sprintf(
            'Email vérifié pour l\'utilisateur %s (ID: %d)',
            $user->getEmail(),
            $user->getId()
        ));

        // Ici, vous pourriez envoyer un email de confirmation, etc.
    }

    /**
     * Gestionnaire pour la complétion du profil
     */
    public function onCompleteProfile(Event $event): void
    {
        /** @var User $user */
        $user = $event->getSubject();

        $this->logger->info(sprintf(
            'Profil complété pour l\'utilisateur %s (ID: %d)',
            $user->getEmail(),
            $user->getId()
        ));
    }

    /**
     * Gestionnaire pour la sélection d'abonnement
     */
    public function onSelectSubscription(Event $event): void
    {
        /** @var User $user */
        $user = $event->getSubject();

        $this->logger->info(sprintf(
            'Abonnement sélectionné pour l\'utilisateur %s (ID: %d)',
            $user->getEmail(),
            $user->getId()
        ));
    }

    /**
     * Gestionnaire pour le traitement du paiement
     */
    public function onProcessPayment(Event $event): void
    {
        /** @var User $user */
        $user = $event->getSubject();

        $this->logger->info(sprintf(
            'Paiement traité pour l\'utilisateur %s (ID: %d)',
            $user->getEmail(),
            $user->getId()
        ));

        // Ici, vous pourriez intégrer avec un service de paiement
    }

    /**
     * Gestionnaire pour la fin de l'onboarding
     */
    public function onCompleteOnboarding(Event $event): void
    {
        /** @var User $user */
        $user = $event->getSubject();

        $this->logger->info(sprintf(
            'Onboarding terminé pour l\'utilisateur %s (ID: %d)',
            $user->getEmail(),
            $user->getId()
        ));

        // Ici, vous pourriez envoyer un email de bienvenue finale, etc.
    }

    /**
     * Vérifie les conditions avant d'autoriser une transition
     */
    public function guardOnboarding(GuardEvent $event): void
    {
        /** @var User $user */
        $user = $event->getSubject();
        $transition = $event->getTransition();

        // Exemple de vérification : s'assurer que l'utilisateur est actif
        if (!$user->isActive()) {
            $event->setBlocked(true, 'Le compte utilisateur n\'est pas actif');
        }

        // Vous pourriez ajouter d'autres vérifications spécifiques à chaque transition
        switch ($transition->getName()) {
            case 'verify_email':
                // Vérifications spécifiques pour la vérification d'email
                break;

            case 'complete_profile':
                // Vérifier que les champs requis du profil sont remplis
                if (empty($user->getFirstName()) || empty($user->getLastName())) {
                    $event->setBlocked(true, 'Le prénom et le nom sont obligatoires');
                }
                break;

            // Ajoutez d'autres cas selon vos besoins
        }
    }

    /**
     * Gestionnaire pour les événements personnalisés d'onboarding
     */
    public function onOnboardingEvent(OnboardingEvent $event): void
    {
        $user = $event->getUser();
        $fromState = $event->getFromState();
        $toState = $event->getToState();
        $context = $event->getContext();

        $this->logger->info(sprintf(
            'Événement d\'onboarding: %s -> %s pour l\'utilisateur %s (ID: %d)',
            $fromState,
            $toState,
            $user->getEmail(),
            $user->getId()
        ), $context);

        // Ici, vous pourriez déclencher des actions spécifiques en fonction des transitions
        // Par exemple, envoyer des emails, créer des tâches, etc.
    }
}
