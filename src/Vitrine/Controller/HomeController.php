<?php

namespace App\Vitrine\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('', name: 'app_home', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->json([
            'status' => 'success',
            'message' => 'Bienvenue sur notre plateforme',
            'version' => '1.0.0',
            'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ]);
    }

    #[Route('/features', name: 'app_features', methods: ['GET'])]
    public function features(): JsonResponse
    {
        return $this->json([
            'status' => 'success',
            'features' => [
                'Gestion des utilisateurs',
                'Espace client sécurisé',
                'Tableau de bord administrateur',
                'API RESTful',
                'Authentification JWT',
            ],
        ]);
    }

    #[Route('/pricing', name: 'app_pricing', methods: ['GET'])]
    public function pricing(): JsonResponse
    {
        return $this->json([
            'status' => 'success',
            'plans' => [
                [
                    'name' => 'Starter',
                    'price' => 0,
                    'features' => ['Fonctionnalités de base', 'Support par email'],
                ],
                [
                    'name' => 'Pro',
                    'price' => 29.99,
                    'features' => ['Toutes les fonctionnalités', 'Support prioritaire', 'Personnalisation'],
                ],
                [
                    'name' => 'Enterprise',
                    'price' => 'Personnalisé',
                    'features' => ['Solution sur mesure', 'Support 24/7', 'Formation incluse'],
                ],
            ],
        ]);
    }
}
