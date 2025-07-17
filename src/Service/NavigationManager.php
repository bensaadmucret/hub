<?php

namespace App\Service;

use App\Repository\PageRepository;
use Symfony\Component\HttpFoundation\RequestStack;

final class NavigationManager
{
    private array $navigation = [];
    
    public function __construct(
        private PageRepository $pageRepository,
        private RequestStack $requestStack,
    ) {
        $this->initializeNavigation();
    }

    private function initializeNavigation(): void
    {
        $homepage = $this->pageRepository->findOneBySlug('acceuil');
        
        if (!$homepage) {
            return;
        }

        $this->navigation = array_filter(
            $homepage->getSections()->toArray(),
            fn($section) => in_array($section->getType(), ['features', 'advantages', 'testimonials', 'contact'])
        );
    }

    public function getMainNavigation(): array
    {
        return array_map(function($section) {
            return [
                'id' => $section->getType(),
                'label' => match($section->getType()) {
                    'features' => 'Fonctionnalités',
                    'advantages' => 'Avantages',
                    'testimonials' => 'Témoignages',
                    'contact' => 'Contact',
                    default => ucfirst($section->getType()),
                },
                'active' => $this->isActive($section->getType())
            ];
        }, $this->navigation);
    }

    private function isActive(string $section): bool
    {
        $request = $this->requestStack->getMainRequest();
        $currentRoute = $request->attributes->get('_route');
        
        if ($currentRoute === 'app_home') {
            $currentSection = $request->query->get('section');
            return $currentSection === $section;
        }
        
        return false;
    }
}
