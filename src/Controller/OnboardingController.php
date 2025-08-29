<?php

namespace App\Controller;

use App\Service\PayloadCmsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class OnboardingController extends AbstractController
{
    /**
     * Page d'accueil du tunnel d'onboarding
     */
    #[Route('/onboarding', name: 'app_onboarding_start')]
    public function start(SessionInterface $session): Response
    {
        // Initialiser les données du tunnel si elles n'existent pas
        if (!$session->has('onboarding_data')) {
            $session->set('onboarding_data', [
                'step' => 1,
                'firstName' => '',
                'lastName' => '',
                'email' => '',
                'password' => '',
                'studyYear' => '',
                'examDate' => '',
                'targetScore' => 0,
                'studyHoursPerWeek' => 0,
                'termsAccepted' => false,
            ]);
        }

        return $this->redirectToRoute('app_onboarding_step1');
    }

    /**
     * Étape 1: Création de compte
     */
    #[Route('/onboarding/step1', name: 'app_onboarding_step1')]
    public function step1(Request $request, SessionInterface $session, ValidatorInterface $validator): Response
    {
        $onboardingData = $session->get('onboarding_data', []);
        $errors = [];

        if ($request->isMethod('POST')) {
            $token = $request->request->get('_token');
            if (!$this->isCsrfTokenValid('onboarding_step1', $token)) {
                return new Response('Invalid CSRF token', 419);
            }
            // Récupérer les données du formulaire
            $firstName = $request->request->get('firstName');
            $lastName = $request->request->get('lastName');
            $email = $request->request->get('email');
            $password = $request->request->get('password');
            $confirmPassword = $request->request->get('confirmPassword');

            // Valider les données
            $constraints = new Assert\Collection([
                'firstName' => [new Assert\NotBlank(['message' => 'Le prénom est requis'])],
                'lastName' => [new Assert\NotBlank(['message' => 'Le nom est requis'])],
                'email' => [
                    new Assert\NotBlank(['message' => 'L\'email est requis']),
                    new Assert\Email(['message' => 'Format d\'email invalide']),
                ],
                'password' => [
                    new Assert\NotBlank(['message' => 'Le mot de passe est requis']),
                    new Assert\Length([
                        'min' => 8,
                        'minMessage' => 'Le mot de passe doit contenir au moins {{ limit }} caractères',
                    ]),
                ],
            ]);

            $dataToValidate = [
                'firstName' => $firstName,
                'lastName' => $lastName,
                'email' => $email,
                'password' => $password,
            ];

            $violations = $validator->validate($dataToValidate, $constraints);

            if (count($violations) > 0) {
                foreach ($violations as $violation) {
                    $propertyPath = $violation->getPropertyPath();
                    $errors[$propertyPath] = $violation->getMessage();
                }
            }

            // Vérifier que les mots de passe correspondent
            if ($password !== $confirmPassword) {
                $errors['confirmPassword'] = 'Les mots de passe ne correspondent pas';
            }


            // Si pas d'erreurs, sauvegarder et passer à l'étape suivante
            if (empty($errors)) {
                $onboardingData['firstName'] = $firstName;
                $onboardingData['lastName'] = $lastName;
                $onboardingData['email'] = $email;
                $onboardingData['password'] = $password;
                $onboardingData['step'] = 2;

                $session->set('onboarding_data', $onboardingData);

                return $this->redirectToRoute('app_onboarding_step2');
            }
        }

        return $this->render('onboarding/step1.html.twig', [
            'component' => 'Onboarding/Step1AccountCreation',
            'componentProps' => [
                'firstName' => $request->isMethod('POST') ? ($request->request->get('firstName', $onboardingData['firstName'] ?? '')) : ($onboardingData['firstName'] ?? ''),
                'lastName' => $request->isMethod('POST') ? ($request->request->get('lastName', $onboardingData['lastName'] ?? '')) : ($onboardingData['lastName'] ?? ''),
                'email' => $request->isMethod('POST') ? ($request->request->get('email', $onboardingData['email'] ?? '')) : ($onboardingData['email'] ?? ''),
                'errors' => $errors,
                'currentStep' => 1
            ],
        ]);
    }

    /**
     * Étape 2: Informations académiques
     */
    #[Route('/onboarding/step2', name: 'app_onboarding_step2')]
    public function step2(Request $request, SessionInterface $session, ValidatorInterface $validator): Response
    {
        $onboardingData = $session->get('onboarding_data', []);
        
        // Rediriger vers l'étape 1 si l'utilisateur n'a pas complété l'étape précédente
        if (!isset($onboardingData['step']) || $onboardingData['step'] < 2) {
            return $this->redirectToRoute('app_onboarding_step1');
        }

        $errors = [];

        if ($request->isMethod('POST')) {
            $token = $request->request->get('_token');
            if (!$this->isCsrfTokenValid('onboarding_step2', $token)) {
                return new Response('Invalid CSRF token', 419);
            }
            // Récupérer les données du formulaire
            $studyYear = $request->request->get('studyYear');
            $examDate = $request->request->get('examDate');

            // Valider les données
            $constraints = new Assert\Collection([
                'studyYear' => [
                    new Assert\NotBlank(['message' => 'L\'année d\'études est requise']),
                    new Assert\Choice([
                        'choices' => ['pass', 'las'],
                        'message' => 'Veuillez sélectionner une année d\'études valide',
                    ]),
                ],
                'examDate' => [
                    new Assert\NotBlank(['message' => 'La date d\'examen est requise']),
                    new Assert\Date(['message' => 'Format de date invalide']),
                ],
            ]);

            $dataToValidate = [
                'studyYear' => $studyYear,
                'examDate' => $examDate,
            ];

            $violations = $validator->validate($dataToValidate, $constraints);

            if (count($violations) > 0) {
                foreach ($violations as $violation) {
                    $propertyPath = $violation->getPropertyPath();
                    $errors[$propertyPath] = $violation->getMessage();
                }
            }

            // Si pas d'erreurs, sauvegarder et passer à l'étape suivante
            if (empty($errors)) {
                $onboardingData['studyYear'] = $studyYear;
                $onboardingData['examDate'] = $examDate;
                $onboardingData['step'] = 3;

                $session->set('onboarding_data', $onboardingData);

                return $this->redirectToRoute('app_onboarding_step3');
            }
        }

        return $this->render('onboarding/step2.html.twig', [
            'component' => 'Onboarding/Step2AcademicInfo',
            'componentProps' => [
                'studyYear' => $request->isMethod('POST') ? ($request->request->get('studyYear', $onboardingData['studyYear'] ?? '')) : ($onboardingData['studyYear'] ?? ''),
                'examDate' => $request->isMethod('POST') ? ($request->request->get('examDate', $onboardingData['examDate'] ?? '')) : ($onboardingData['examDate'] ?? ''),
                'errors' => $errors,
                'currentStep' => 2
            ],
        ]);
    }

    /**
     * Étape 3: Objectifs d'étude
     */
    #[Route('/onboarding/step3', name: 'app_onboarding_step3')]
    public function step3(Request $request, SessionInterface $session, ValidatorInterface $validator): Response
    {
        $onboardingData = $session->get('onboarding_data', []);
        
        // Rediriger vers l'étape 1 si l'utilisateur n'a pas complété les étapes précédentes
        if (!isset($onboardingData['step']) || $onboardingData['step'] < 3) {
            return $this->redirectToRoute('app_onboarding_step2');
        }

        $errors = [];

        if ($request->isMethod('POST')) {
            $token = $request->request->get('_token');
            if (!$this->isCsrfTokenValid('onboarding_step3', $token)) {
                return new Response('Invalid CSRF token', 419);
            }
            // Récupérer les données du formulaire
            $targetScore = (int) $request->request->get('targetScore');
            $studyHoursPerWeek = (int) $request->request->get('studyHoursPerWeek');

            // Valider les données
            $constraints = new Assert\Collection([
                'targetScore' => [
                    new Assert\NotBlank(['message' => 'L\'objectif de score est requis']),
                    new Assert\Range([
                        'min' => 0,
                        'max' => 100,
                        'notInRangeMessage' => 'L\'objectif de score doit être entre {{ min }} et {{ max }}',
                    ]),
                ],
                'studyHoursPerWeek' => [
                    new Assert\NotBlank(['message' => 'Les heures d\'étude sont requises']),
                    new Assert\Range([
                        'min' => 1,
                        'max' => 80,
                        'notInRangeMessage' => 'Les heures d\'étude doivent être entre {{ min }} et {{ max }}',
                    ]),
                ],
            ]);

            $dataToValidate = [
                'targetScore' => $targetScore,
                'studyHoursPerWeek' => $studyHoursPerWeek,
            ];

            $violations = $validator->validate($dataToValidate, $constraints);

            if (count($violations) > 0) {
                foreach ($violations as $violation) {
                    $propertyPath = $violation->getPropertyPath();
                    $errors[$propertyPath] = $violation->getMessage();
                }
            }

            // Si pas d'erreurs, sauvegarder et passer à l'étape suivante
            if (empty($errors)) {
                $onboardingData['targetScore'] = $targetScore;
                $onboardingData['studyHoursPerWeek'] = $studyHoursPerWeek;
                $onboardingData['step'] = 4;

                $session->set('onboarding_data', $onboardingData);

                return $this->redirectToRoute('app_onboarding_step4');
            }
        }

        return $this->render('onboarding/step3.html.twig', [
            'component' => 'Onboarding/Step3StudyObjectives',
            'componentProps' => [
                'targetScore' => $request->isMethod('POST') ? ($request->request->get('targetScore', $onboardingData['targetScore'] ?? '')) : ($onboardingData['targetScore'] ?? ''),
                'studyHoursPerWeek' => $request->isMethod('POST') ? ($request->request->get('studyHoursPerWeek', $onboardingData['studyHoursPerWeek'] ?? '')) : ($onboardingData['studyHoursPerWeek'] ?? ''),
                'errors' => $errors,
                'currentStep' => 3
            ],
        ]);
    }

    /**
     * Étape 4: Confirmation
     */
    #[Route('/onboarding/step4', name: 'app_onboarding_step4')]
    public function step4(Request $request, SessionInterface $session): Response
    {
        $onboardingData = $session->get('onboarding_data', []);
        
        // Rediriger vers l'étape 1 si l'utilisateur n'a pas complété les étapes précédentes
        if (!isset($onboardingData['step']) || $onboardingData['step'] < 4) {
            return $this->redirectToRoute('app_onboarding_step3');
        }

        $errors = [];

        if ($request->isMethod('POST')) {
            $token = $request->request->get('_token');
            if (!$this->isCsrfTokenValid('onboarding_step4', $token)) {
                return new Response('Invalid CSRF token', 419);
            }
            // Récupérer les données du formulaire
            $termsAccepted = $request->request->has('termsAccepted');

            if (!$termsAccepted) {
                $errors['termsAccepted'] = 'Vous devez accepter les conditions d\'utilisation';
            } else {
                $onboardingData['termsAccepted'] = true;
                $onboardingData['step'] = 5; // Passer à l'étape de paiement
                $session->set('onboarding_data', $onboardingData);
                
                return $this->redirectToRoute('app_onboarding_payment');
            }
        }

        return $this->render('onboarding/step4.html.twig', [
            'component' => 'Onboarding/Step4Confirmation',
            'componentProps' => [
                'userData' => $onboardingData,
                'termsAccepted' => $onboardingData['termsAccepted'] ?? false,
                'errors' => $errors,
                'currentStep' => 4
            ],
        ]);
    }

    /**
     * Étape de paiement
     */
    #[Route('/onboarding/payment', name: 'app_onboarding_payment')]
    public function payment(SessionInterface $session): Response
    {
        $onboardingData = $session->get('onboarding_data', []);
        
        // Rediriger vers l'étape 1 si l'utilisateur n'a pas complété les étapes précédentes
        if (!isset($onboardingData['step']) || $onboardingData['step'] < 5 || !$onboardingData['termsAccepted']) {
            return $this->redirectToRoute('app_onboarding_step4');
        }

        // Configuration pour Paddle
        $priceId = $this->getParameter('paddle.price_id');
        $customerEmail = $onboardingData['email'];
        $customerName = $onboardingData['firstName'] . ' ' . $onboardingData['lastName'];

        return $this->render('onboarding/payment.html.twig', [
            'componentProps' => [
                'priceId' => $priceId,
                'customerEmail' => $customerEmail,
                'customerName' => $customerName,
                'currentStep' => 5
            ],
        ]);
    }

    /**
     * Traitement du paiement côté serveur (fallback)
     */
    #[Route('/onboarding/payment/process', name: 'app_onboarding_payment_process', methods: ['POST'])]
    public function processPayment(Request $request, SessionInterface $session): Response
    {
        $onboardingData = $session->get('onboarding_data', []);
        
        // Vérifier que l'utilisateur a bien complété les étapes précédentes
        if (!isset($onboardingData['step']) || $onboardingData['step'] < 5) {
            return $this->redirectToRoute('app_onboarding_start');
        }

        $priceId = $request->request->get('priceId');
        
        // Créer une URL de checkout Paddle côté serveur
        $paddleApiKey = $this->getParameter('paddle.api_key');
        $paddleApiBase = $this->getParameter('paddle.api_base');
        
        // Ici, vous implémenteriez l'appel à l'API Paddle pour créer une transaction
        // Pour l'exemple, nous allons simplement rediriger vers une URL simulée
        $checkoutUrl = $paddleApiBase . '/checkout?price_id=' . $priceId . '&customer_email=' . urlencode($onboardingData['email']);
        
        // Stocker l'ID de transaction dans la session si nécessaire
        $onboardingData['transactionInitiated'] = true;
        $session->set('onboarding_data', $onboardingData);
        
        // Rediriger vers l'URL de checkout Paddle
        return new RedirectResponse($checkoutUrl, 303);
    }

    /**
     * Webhook pour Paddle
     */
    #[Route('/webhooks/paddle', name: 'app_webhook_paddle', methods: ['POST'])]
    public function paddleWebhook(Request $request, PayloadCmsService $payloadService): Response
    {
        // Vérifier la signature du webhook
        $payload = $request->request->all();
        $signature = $request->headers->get('Paddle-Signature');
        
        // Vous devriez implémenter une vérification de signature ici
        // Si la signature est invalide, retourner une erreur 401
        
        // Traiter l'événement
        $eventType = $payload['event_type'] ?? '';
        
        if ($eventType === 'transaction.completed') {
            // Récupérer les données de la transaction
            $transactionId = $payload['data']['id'] ?? null;
            $customerId = $payload['data']['customer_id'] ?? null;
            $customerEmail = $payload['data']['customer_email'] ?? null;
            
            // Ici, vous devriez récupérer les données utilisateur associées à cet email
            // Pour l'exemple, nous allons simuler l'envoi à Payload CMS
            
            try {
                // Simuler l'envoi des données à Payload CMS
                $result = $payloadService->createUser([
                    'email' => $customerEmail,
                    'transactionId' => $transactionId,
                    // Autres données nécessaires
                ]);
                
                // Loguer le résultat
                // $this->logger->info('User created in Payload CMS', ['result' => $result]);
                
                return new Response('Webhook processed successfully', 200);
            } catch (\Exception $e) {
                // $this->logger->error('Error processing webhook', ['error' => $e->getMessage()]);
                return new Response('Error processing webhook', 500);
            }
        }
        
        return new Response('Event type not handled', 200);
    }

    /**
     * Page de succès après paiement
     */
    #[Route('/onboarding/success', name: 'app_onboarding_success')]
    public function success(Request $request, SessionInterface $session): Response
    {
        $transactionId = $request->query->get('transaction_id');
        $onboardingData = $session->get('onboarding_data', []);
        
        // Vérifier que l'utilisateur a bien complété toutes les étapes et a un ID de transaction
        if (!isset($onboardingData['step']) || $onboardingData['step'] < 5 || !$onboardingData['termsAccepted']) {
            return $this->redirectToRoute('app_onboarding_start');
        }

        // Ajouter l'ID de transaction aux données si fourni
        if ($transactionId) {
            $onboardingData['transactionId'] = $transactionId;
            $session->set('onboarding_data', $onboardingData);
        }
        
        // Ajouter un message flash de succès
        $this->addFlash('success', 'Félicitations ! Votre inscription est complète et votre paiement a été traité avec succès.');

        // Nettoyer les données de session après succès
        $session->remove('onboarding_data');

        return $this->render('onboarding/success.html.twig', [
            'pageTitle' => 'Félicitations !',
            'pageDescription' => 'Votre inscription est complète et votre paiement a été traité avec succès.'
        ]);
    }
}
