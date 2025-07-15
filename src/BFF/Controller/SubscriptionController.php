<?php

namespace App\BFF\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SubscriptionController extends AbstractController
{
    #[Route('/subscription', name: 'subscription')]
    public function index(): Response
    {
        // Affichage d'une page d'abonnement simple (à personnaliser selon besoin)
        return $this->render('subscription/index.html.twig', [
            // Passer ici les variables nécessaires à la vue
        ]);
    }
}
