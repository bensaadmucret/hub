<?php

namespace App\Controller\Admin;

use App\Core\Entity\User;
use App\Entity\Contact;
use App\Entity\Page;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[AdminDashboard]
class DashboardController extends AbstractDashboardController
{
    public function __construct(private AdminUrlGenerator $adminUrlGenerator)
    {
    }

    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        // Option 1. Redirect to your most important CRUD controller
        $url = $this->adminUrlGenerator->setController(PageCrudController::class)->generateUrl();

        return $this->redirect($url);

        // Option 2. Render a custom dashboard
        // return $this->render('some/path/my-dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('App Demo');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');

        yield MenuItem::section('Gestion du contenu');
        yield MenuItem::linkToCrud('Pages', 'fas fa-file-alt', Page::class);

        yield MenuItem::section('Gestion des contacts');
        yield MenuItem::linkToCrud('Messages de contact', 'fas fa-envelope', Contact::class);

        yield MenuItem::section('Utilisateurs');
        yield MenuItem::linkToCrud('Utilisateurs', 'fas fa-users', User::class);
    }
}
