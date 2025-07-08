<?php

namespace App\Onboarding\Controller;

use App\Core\Entity\User;
use App\Onboarding\Workflow\OnboardingWorkflow;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[Route('/onboarding')]
class OnboardingController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private WorkflowInterface $onboardingWorkflow,
        private ValidatorInterface $validator
    ) {
    }

    #[Route('/start', name: 'onboarding_start', methods: ['POST'])]
    public function start(Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json([
                'status' => 'error',
                'message' => 'User not authenticated',
            ], 401);
        }

        // Vérifier si l'utilisateur a déjà terminé l'onboarding
        if ($this->onboardingWorkflow->can($user, 'complete_onboarding')) {
            return $this->json([
                'status' => 'success',
                'message' => 'Onboarding already completed',
                'current_state' => 'onboarding_complete',
            ]);
        }

        // Démarrer le processus d'onboarding
        $this->onboardingWorkflow->getMarking($user);

        return $this->json([
            'status' => 'success',
            'message' => 'Onboarding started',
            'current_state' => 'start',
            'next_steps' => ['verify_email'],
        ]);
    }

    #[Route('/verify-email', name: 'onboarding_verify_email', methods: ['POST'])]
    public function verifyEmail(Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json([
                'status' => 'error',
                'message' => 'User not authenticated',
            ], 401);
        }

        // Simuler la vérification d'email
        if ($this->onboardingWorkflow->can($user, 'verify_email')) {
            $this->onboardingWorkflow->apply($user, 'verify_email');
            $this->entityManager->flush();

            return $this->json([
                'status' => 'success',
                'message' => 'Email verified',
                'current_state' => 'email_verified',
                'next_steps' => ['complete_profile'],
            ]);
        }

        return $this->json([
            'status' => 'error',
            'message' => 'Cannot verify email in current state',
            'current_state' => $this->onboardingWorkflow->getMarking($user)->getPlaces(),
        ], 400);
    }

    #[Route('/complete-profile', name: 'onboarding_complete_profile', methods: ['POST'])]
    public function completeProfile(Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json([
                'status' => 'error',
                'message' => 'User not authenticated',
            ], 401);
        }

        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['status' => 'error', 'message' => 'Invalid JSON payload.'], 400);
        }

        $constraints = new Assert\Collection([
            'firstName' => new Assert\Optional([new Assert\NotBlank(), new Assert\Type('string')]),
            'lastName' => new Assert\Optional([new Assert\NotBlank(), new Assert\Type('string')]),
        ]);

        $violations = $this->validator->validate($data, $constraints);

        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[trim($violation->getPropertyPath(), '[]')] = $violation->getMessage();
            }
            return $this->json(['status' => 'error', 'message' => 'Validation failed', 'errors' => $errors], 400);
        }

        // Mettre à jour le profil utilisateur
        if (array_key_exists('firstName', $data)) {
            $user->setFirstName($data['firstName']);
        }

        if (array_key_exists('lastName', $data)) {
            $user->setLastName($data['lastName']);
        }

        if ($this->onboardingWorkflow->can($user, 'complete_profile')) {
            $this->onboardingWorkflow->apply($user, 'complete_profile');
            $this->entityManager->flush();

            return $this->json([
                'status' => 'success',
                'message' => 'Profile completed',
                'current_state' => 'profile_completed',
                'next_steps' => ['select_subscription'],
            ]);
        }

        return $this->json([
            'status' => 'error',
            'message' => 'Cannot complete profile in current state',
            'current_state' => $this->onboardingWorkflow->getMarking($user)->getPlaces(),
        ], 400);
    }

    #[Route('/select-subscription', name: 'onboarding_select_subscription', methods: ['POST'])]
    public function selectSubscription(Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json([
                'status' => 'error',
                'message' => 'User not authenticated',
            ], 401);
        }

        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['status' => 'error', 'message' => 'Invalid JSON payload.'], 400);
        }

        $constraints = new Assert\Collection([
            'planId' => [new Assert\NotBlank(), new Assert\Type('string')],
        ]);

        $violations = $this->validator->validate($data, $constraints);

        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[trim($violation->getPropertyPath(), '[]')] = $violation->getMessage();
            }
            return $this->json(['status' => 'error', 'message' => 'Validation failed', 'errors' => $errors], 400);
        }

        if ($this->onboardingWorkflow->can($user, 'select_subscription')) {
            $this->onboardingWorkflow->apply($user, 'select_subscription');
            $this->entityManager->flush();

            return $this->json([
                'status' => 'success',
                'message' => 'Subscription selected',
                'current_state' => 'subscription_selected',
                'next_steps' => ['process_payment'],
            ]);
        }

        return $this->json([
            'status' => 'error',
            'message' => 'Cannot select subscription in current state',
            'current_state' => $this->onboardingWorkflow->getMarking($user)->getPlaces(),
        ], 400);
    }

    #[Route('/process-payment', name: 'onboarding_process_payment', methods: ['POST'])]
    public function processPayment(Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json([
                'status' => 'error',
                'message' => 'User not authenticated',
            ], 401);
        }

        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['status' => 'error', 'message' => 'Invalid JSON payload.'], 400);
        }

        $constraints = new Assert\Collection([
            'paymentMethodId' => [new Assert\NotBlank(), new Assert\Type('string')],
        ]);

        $violations = $this->validator->validate($data, $constraints);

        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[trim($violation->getPropertyPath(), '[]')] = $violation->getMessage();
            }
            return $this->json(['status' => 'error', 'message' => 'Validation failed', 'errors' => $errors], 400);
        }

        if ($this->onboardingWorkflow->can($user, 'process_payment')) {
            $this->onboardingWorkflow->apply($user, 'process_payment');
            $this->entityManager->flush();

            return $this->json([
                'status' => 'success',
                'message' => 'Payment processed',
                'current_state' => 'payment_processed',
                'next_steps' => ['complete_onboarding'],
            ]);
        }

        return $this->json([
            'status' => 'error',
            'message' => 'Cannot process payment in current state',
            'current_state' => $this->onboardingWorkflow->getMarking($user)->getPlaces(),
        ], 400);
    }

    #[Route('/complete', name: 'onboarding_complete', methods: ['POST'])]
    public function complete(Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json([
                'status' => 'error',
                'message' => 'User not authenticated',
            ], 401);
        }

        if ($this->onboardingWorkflow->can($user, 'complete_onboarding')) {
            $this->onboardingWorkflow->apply($user, 'complete_onboarding');
            $this->entityManager->flush();

            return $this->json([
                'status' => 'success',
                'message' => 'Onboarding completed successfully',
                'current_state' => 'onboarding_complete',
            ]);
        }

        return $this->json([
            'status' => 'error',
            'message' => 'Cannot complete onboarding in current state',
            'current_state' => $this->onboardingWorkflow->getMarking($user)->getPlaces(),
        ], 400);
    }

    #[Route('/status', name: 'onboarding_status', methods: ['GET'])]
    public function status(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json([
                'status' => 'error',
                'message' => 'User not authenticated',
            ], 401);
        }

        $marking = $this->onboardingWorkflow->getMarking($user);
        $currentState = $marking->getPlaces();
        $currentState = !empty($currentState) ? array_key_first($currentState) : 'start';

        $nextSteps = [];

        switch ($currentState) {
            case 'start':
                $nextSteps[] = 'verify_email';
                break;
            case 'email_verified':
                $nextSteps[] = 'complete_profile';
                break;
            case 'profile_completed':
                $nextSteps[] = 'select_subscription';
                break;
            case 'subscription_selected':
                $nextSteps[] = 'process_payment';
                break;
            case 'payment_processed':
                $nextSteps[] = 'complete_onboarding';
                break;
        }

        return $this->json([
            'status' => 'success',
            'current_state' => $currentState,
            'is_completed' => $currentState === 'onboarding_complete',
            'next_steps' => $nextSteps,
        ]);
    }
}
