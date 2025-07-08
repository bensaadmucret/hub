<?php

namespace App\Onboarding\Workflow;

use App\Core\Entity\User;
use Symfony\Component\Workflow\DefinitionBuilder;
use Symfony\Component\Workflow\MarkingStore\MethodMarkingStore;
use Symfony\Component\Workflow\MarkingStore\MarkingStoreInterface;
use Symfony\Component\Workflow\Transition;
use Symfony\Component\Workflow\Workflow;
use Symfony\Component\Workflow\Definition;

class OnboardingWorkflow
{
    public const WORKFLOW_NAME = 'onboarding';

    // États du workflow
    public const STATE_START = 'start';
    public const STATE_EMAIL_VERIFIED = 'email_verified';
    public const STATE_PROFILE_COMPLETED = 'profile_completed';
    public const STATE_SUBSCRIPTION_SELECTED = 'subscription_selected';
    public const STATE_PAYMENT_PROCESSED = 'payment_processed';
    public const STATE_ONBOARDING_COMPLETE = 'onboarding_complete';

    // Transitions
    public const TRANSITION_VERIFY_EMAIL = 'verify_email';
    public const TRANSITION_COMPLETE_PROFILE = 'complete_profile';
    public const TRANSITION_SELECT_SUBSCRIPTION = 'select_subscription';
    public const TRANSITION_PROCESS_PAYMENT = 'process_payment';
    public const TRANSITION_COMPLETE_ONBOARDING = 'complete_onboarding';

    /**
     * Crée la définition du workflow
     */
    public function createDefinition(): Definition
    {
        $builder = new DefinitionBuilder();

        return $builder
            // Définition des états possibles
            ->addPlaces([
                self::STATE_START,
                self::STATE_EMAIL_VERIFIED,
                self::STATE_PROFILE_COMPLETED,
                self::STATE_SUBSCRIPTION_SELECTED,
                self::STATE_PAYMENT_PROCESSED,
                self::STATE_ONBOARDING_COMPLETE,
            ])

            // Définition des transitions
            ->addTransition(new Transition(
                self::TRANSITION_VERIFY_EMAIL,
                self::STATE_START,
                self::STATE_EMAIL_VERIFIED
            ))
            ->addTransition(new Transition(
                self::TRANSITION_COMPLETE_PROFILE,
                self::STATE_EMAIL_VERIFIED,
                self::STATE_PROFILE_COMPLETED
            ))
            ->addTransition(new Transition(
                self::TRANSITION_SELECT_SUBSCRIPTION,
                self::STATE_PROFILE_COMPLETED,
                self::STATE_SUBSCRIPTION_SELECTED
            ))
            ->addTransition(new Transition(
                self::TRANSITION_PROCESS_PAYMENT,
                self::STATE_SUBSCRIPTION_SELECTED,
                self::STATE_PAYMENT_PROCESSED
            ))
            ->addTransition(new Transition(
                self::TRANSITION_COMPLETE_ONBOARDING,
                self::STATE_PAYMENT_PROCESSED,
                self::STATE_ONBOARDING_COMPLETE
            ))
            ->build();
    }

    /**
     * Crée le système de marquage pour le workflow
     */
    public function createMarkingStore(): MarkingStoreInterface
    {
        return new MethodMarkingStore(true, 'currentPlace');
    }

    /**
     * Crée une instance du workflow
     */
    public function createWorkflow(): Workflow
    {
        return new Workflow(
            $this->createDefinition(),
            $this->createMarkingStore(),
            null, // dispatcher d'événements (sera injecté par le conteneur)
            self::WORKFLOW_NAME
        );
    }

    /**
     * Liste des états du workflow
     */
    /**
     * @return array<string, string>
     */
    public static function getStates(): array
    {
        return [
            self::STATE_START => 'Démarrage',
            self::STATE_EMAIL_VERIFIED => 'Email vérifié',
            self::STATE_PROFILE_COMPLETED => 'Profil complété',
            self::STATE_SUBSCRIPTION_SELECTED => 'Abonnement choisi',
            self::STATE_PAYMENT_PROCESSED => 'Paiement effectué',
            self::STATE_ONBOARDING_COMPLETE => 'Onboarding terminé',
        ];
    }

    /**
     * Liste des transitions disponibles
     */
    /**
     * @return array<string, string>
     */
    public static function getTransitions(): array
    {
        return [
            self::TRANSITION_VERIFY_EMAIL => 'Vérifier l\'email',
            self::TRANSITION_COMPLETE_PROFILE => 'Compléter le profil',
            self::TRANSITION_SELECT_SUBSCRIPTION => 'Choisir un abonnement',
            self::TRANSITION_PROCESS_PAYMENT => 'Traiter le paiement',
            self::TRANSITION_COMPLETE_ONBOARDING => 'Terminer l\'onboarding',
        ];
    }

    /**
     * Vérifie si un état est un état final
     */
    public static function isFinalState(string $state): bool
    {
        return $state === self::STATE_ONBOARDING_COMPLETE;
    }
}
