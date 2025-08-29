<?php

namespace App\Twig\Components\Onboarding;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('Onboarding/OnboardingLayout')]
class OnboardingLayout
{
    public int $currentStep = 1;
    public string $pageTitle = 'Créez votre profil';
    public string $pageDescription = 'Complétez les informations pour personnaliser votre expérience';
    public $content = null;
}
