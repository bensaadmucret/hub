<?php

namespace App\BFF\Service;

class HomePageService
{
    /**
     * @return array<
     *     array{
     *         title: string,
     *         description: string,
     *         icon: string
     *     }
     * >
     */
    public function getFeatures(): array 
    {
        // Logique pour récupérer les fonctionnalités
        return [];
    }

    /**
     * @return array{
     *     title: string,
     *     description: string,
     *     buttonText: string,
     *     buttonUrl: string
     * }
     */
    public function getCta(): array 
    {
        // Logique pour récupérer le CTA
        return [
            'title' => '',
            'description' => '',
            'buttonText' => '',
            'buttonUrl' => ''
        ];
    }
}
