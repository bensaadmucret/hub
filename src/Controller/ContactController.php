<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\ContactDto;
use App\Service\ContactService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ContactController extends AbstractController
{
    public function __construct(
        private readonly ContactService $contactService,
        private readonly LoggerInterface $logger,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly ValidatorInterface $validator
    ) {
    }

    #[Route('/contact', name: 'app_contact', methods: ['GET', 'POST'])]
    public function contact(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            // Vérification du token CSRF
            $token = $request->request->get('_token');
            
            if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('contact_form', $token))) {
                if ($request->isXmlHttpRequest()) {
                    return $this->json(['error' => 'Session expirée. Veuillez recharger la page et réessayer.'], 419);
                }
                $this->addFlash('error', 'Session expirée. Veuillez réessayer.');
                return $this->redirect($request->headers->get('referer', $this->generateUrl('app_home')));
            }

            // Vérification du honeypot
            if (!empty($request->request->get('website'))) {
                $this->logger->info('Honeypot triggered in contact form', ['ip' => $request->getClientIp()]);
                if ($request->isXmlHttpRequest()) {
                    return $this->json(['success' => 'Votre message a bien été envoyé.']);
                }
                $this->addFlash('success', 'Votre message a bien été envoyé.');
                return $this->redirect($request->headers->get('referer', $this->generateUrl('app_home')));
            }

            try {
                // Création du DTO à partir des données du formulaire
                $dto = new ContactDto();
                
                // Récupération dynamique des champs du formulaire
                $formData = $request->request->all();
                
                // Mappage des champs du formulaire vers le DTO
                foreach ($formData as $field => $value) {
                    if (property_exists($dto, $field)) {
                        // Conversion des types si nécessaire
                        $reflection = new \ReflectionProperty($dto, $field);
                        $type = $reflection->getType();
                        
                        if ($type && !$type->isBuiltin()) {
                            continue;
                        }
                        
                        // Gestion spéciale pour le consentement
                        if ($field === 'consent') {
                            $dto->$field = ($value === '1' || $value === true || $value === 'true');
                            continue;
                        }
                        
                        $value = match ($type ? $type->getName() : '') {
                            'int' => (int) $value,
                            'float' => (float) $value,
                            'bool' => (bool) $value,
                            default => is_string($value) ? trim($value) : $value
                        };
                        
                        $dto->$field = $value;
                    }
                }
                
                // Validation du DTO
                $errors = $this->validator->validate($dto);
                
                if (count($errors) > 0) {
                    $errorMessages = [];
                    foreach ($errors as $error) {
                        $errorMessages[$error->getPropertyPath()] = $error->getMessage();
                    }
                    
                    if ($request->isXmlHttpRequest()) {
                        return $this->json([
                            'errors' => $errorMessages,
                            'message' => 'Veuillez corriger les erreurs du formulaire.'
                        ], 400);
                    }
                    
                    foreach ($errorMessages as $error) {
                        $this->addFlash('error', $error);
                    }
                    return $this->redirect($request->headers->get('referer', $this->generateUrl('app_home')));
                }
                
                // Traitement du formulaire via le service
                $this->contactService->process($dto);
                
                if ($request->isXmlHttpRequest()) {
                    return $this->json([
                        'success' => 'Votre message a bien été envoyé. Nous vous répondrons dès que possible.'
                    ]);
                }
                
                $this->addFlash('success', 'Votre message a bien été envoyé. Nous vous répondrons dès que possible.');
                return $this->redirect($request->headers->get('referer', $this->generateUrl('app_home')) . '#contact');
                
            } catch (\Exception $e) {
                $this->logger->error('Error processing contact form', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                if ($request->isXmlHttpRequest()) {
                    return $this->json([
                        'error' => 'Une erreur est survenue lors de l\'envoi du message. Veuillez réessayer plus tard.'
                    ], 500);
                }
                
                $this->addFlash('error', 'Une erreur est survenue lors de l\'envoi du message. Veuillez réessayer plus tard.');
            }
        }

        // Si c'est une requête AJAX, on retourne une réponse JSON vide
        if ($request->isXmlHttpRequest()) {
            return $this->json([], 200);
        }
        
        // Sinon, on redirige vers la page d'accueil avec une ancre vers la section contact
        return $this->redirect($this->generateUrl('app_home') . '#contact');
    }
}
