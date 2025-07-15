<?php

namespace App\Controller;

use App\Entity\Page;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PageController extends AbstractController
{
    #[Route('/{slug}', name: 'app_page_show', priority: -1)]
    public function show(#[MapEntity(mapping: ['slug' => 'slug'])] Page $page): Response
    {
        // La page est directement récupérée par le ParamConverter de Symfony grâce au {slug}
        // Si aucune page avec ce slug n'est trouvée, une erreur 404 est automatiquement levée.

        return $this->render('page/show.html.twig', [
            'page' => $page,
        ]);
    }
}
