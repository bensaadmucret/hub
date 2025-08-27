<?php

namespace App\Controller;

use App\Repository\PageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    private const HOMEPAGE_SLUG = 'acceuil';

    public function __construct(private PageRepository $pageRepository)
    {
    }

    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        $page = $this->pageRepository->findOneBySlug(self::HOMEPAGE_SLUG);
        if (!$page) {
            throw $this->createNotFoundException('Page d\'accueil non trouvée');
        }


        return $this->render('page/show.html.twig', [
            'page' => $page,
        ]);
    }
}
