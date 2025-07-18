<?php

namespace App\Controller;

use App\Entity\Page;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class PageController extends AbstractController
{
   #[Route('/{slug}', name: 'app_page_show')]
    public function show(#[MapEntity(mapping: ['slug' => 'slug'])] Page $page): Response
    {
   
        return $this->render('page/show.html.twig', [
            'page' => $page,
        ]);
    }
}
