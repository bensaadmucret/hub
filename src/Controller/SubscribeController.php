<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;

final class SubscribeController extends AbstractController
{
    public function __construct(
        private readonly KernelInterface $kernel,
        #[Autowire(env: 'PADDLE_PRICE_ID')] private readonly ?string $paddlePriceId = null,
        #[Autowire(env: 'PADDLE_CLIENT_TOKEN')] private readonly ?string $paddleClientToken = null,
    ) {
    }

    #[Route(path: '/subscribe', name: 'subscribe_page', methods: ['GET'], priority: 100)]
    public function index(): Response
    {
        $appEnv = $this->kernel->getEnvironment();
        $priceId = $this->paddlePriceId ?? '';
        // Optional values; keep empty string by default if not configured
        $clientToken = (string) ($this->paddleClientToken ?? '');

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
        ]);
    }
}
