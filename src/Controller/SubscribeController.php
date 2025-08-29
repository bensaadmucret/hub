<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use App\Integration\Paddle\PaddleCheckoutClient;

final class SubscribeController extends AbstractController
{
    public function __construct(
        private readonly KernelInterface $kernel,
        private readonly PaddleCheckoutClient $checkoutClient,
        private readonly RouterInterface $router,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        #[Autowire(env: 'PADDLE_PRICE_ID')] private readonly ?string $paddlePriceId = null,
        #[Autowire(env: 'PADDLE_CLIENT_TOKEN')] private readonly ?string $paddleClientToken = null,
        #[Autowire(env: 'default::PADDLE_JS_CHECKOUT_ENABLED')] private readonly ?string $paddleJsCheckoutEnabled = null,
    ) {
    }

    #[Route(path: '/subscribe', name: 'subscribe_page', methods: ['GET'], priority: 100)]
    public function index(): Response
    {
        $appEnv = $this->kernel->getEnvironment();
        $priceId = $this->paddlePriceId ?? '';
        // Optional values; keep empty string by default if not configured
        $clientToken = (string) ($this->paddleClientToken ?? '');
        $paddleJsEnabled = filter_var($this->paddleJsCheckoutEnabled ?? '0', FILTER_VALIDATE_BOOL);

        // If a security layer exists and user is logged, you can prefill email.
        $customerEmail = '';
        $user = $this->getUser();
        if ($user instanceof UserInterface) {
            $customerEmail = (string) $user->getUserIdentifier();
        }

        if ($priceId === '') {
            $this->addFlash('warning', 'PADDLE_PRICE_ID manquant. Ajoutez-le dans votre fichier .env.local');
        }

        return $this->render('subscribe/index.html.twig', [
            'app_env' => $appEnv,
            'paddle_price_id' => $priceId,
            'paddle_client_token' => $clientToken,
            'customer_email' => $customerEmail,
            'paddle_js_enabled' => $paddleJsEnabled,
        ]);
    }

    #[Route(path: '/subscribe/create', name: 'subscribe_create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        // CSRF protection
        $submittedToken = (string) $request->request->get('_token', '');
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('subscribe_create', $submittedToken))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('subscribe_page');
        }

        $priceId = (string) ($this->paddlePriceId ?? '');
        if ($priceId === '') {
            $this->addFlash('error', 'Configuration manquante: PADDLE_PRICE_ID.');
            return $this->redirectToRoute('subscribe_page');
        }

        $paddleJsEnabled = filter_var($this->paddleJsCheckoutEnabled ?? '0', FILTER_VALIDATE_BOOL);

        // Si Paddle.js est activé, ne pas faire de redirection - le checkout se fait côté client
        if ($paddleJsEnabled) {
            $this->addFlash('info', 'Checkout Paddle.js activé - le paiement se fait côté client.');
            return $this->redirectToRoute('subscribe_page');
        }

        // Build return URLs via router
        $successUrl = $this->router->generate('subscribe_success', [], RouterInterface::ABSOLUTE_URL);
        $cancelUrl  = $this->router->generate('subscribe_cancel', [], RouterInterface::ABSOLUTE_URL);

        // Optional customer email if user is authenticated
        $email = null;
        $user = $this->getUser();
        if ($user instanceof UserInterface) {
            $email = (string) $user->getUserIdentifier();
        }

        try {
            $redirectUrl = $this->checkoutClient->createCheckoutSession($priceId, $email, $successUrl, $cancelUrl);
            return new RedirectResponse($redirectUrl, 303);
        } catch (\Throwable $e) {
            $this->addFlash('error', 'Impossible de créer la session de paiement: ' . $e->getMessage());
            return $this->redirectToRoute('subscribe_page');
        }
    }

    #[Route(path: '/subscribe/success', name: 'subscribe_success', methods: ['GET'])]
    public function success(): Response
    {
        $this->addFlash('success', 'Paiement initié avec succès. Vous recevrez un e-mail une fois l’activation confirmée.');
        return $this->redirectToRoute('subscribe_page');
    }

    #[Route(path: '/subscribe/cancel', name: 'subscribe_cancel', methods: ['GET'])]
    public function cancel(): Response
    {
        $this->addFlash('info', 'Le paiement a été annulé. Vous pouvez réessayer quand vous voulez.');
        return $this->redirectToRoute('subscribe_page');
    }
}
