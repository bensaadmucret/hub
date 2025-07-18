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
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Repository\PageRepository;

final class ContactController extends AbstractController
{
    private const CONTACT_PAGE_SLUG = 'contact';

    public function __construct(
        private readonly ContactService $contactService,
        private readonly LoggerInterface $logger,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly ValidatorInterface $validator,
        private readonly SerializerInterface $serializer,
        private readonly PageRepository $pageRepository
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

                $formData = $request->request->all();
                $consentValue = $formData['consent'] ?? null;
                unset($formData['consent']);
                
                $jsonData = json_encode($formData);
                $dto = $this->serializer->deserialize(
                    $jsonData,
                    ContactDto::class,
                    'json',
                    [
                        AbstractNormalizer::OBJECT_TO_POPULATE => new ContactDto(),
                        AbstractNormalizer::IGNORED_ATTRIBUTES => ['_token', 'website']
                    ]
                );
                
                // Appliquer la logique de conversion après la désérialisation
                if (isset($consentValue)) {
                    $dto->consent = ($consentValue === '1' || $consentValue === true || $consentValue === 'true');
                }
                
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

        // Si c'est une requête AJAX, on retourne une réponse 204 No Content
        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(null, Response::HTTP_NO_CONTENT);

        }

        $page = $this->pageRepository->findOneBySlug(self::CONTACT_PAGE_SLUG);
        if (!$page) {
            throw $this->createNotFoundException('Page de contact non trouvée');
        }
  

        return $this->render('page/show.html.twig', [
            'page' => $page,
        ]);
        
    }
}
