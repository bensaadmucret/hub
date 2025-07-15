<?php

namespace App\BFF\Controller;

use App\BFF\Service\HomePageService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    public function __construct(
        private HomePageService $homePageService
    ) {}

    #[Route('/', name: 'home')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig', [
            'features' => $this->homePageService->getFeatures(),
            'cta' => $this->homePageService->getCta()
        ]);
    }
}
