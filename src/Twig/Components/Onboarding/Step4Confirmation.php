<?php

namespace App\Twig\Components\Onboarding;

use Symfony\Component\Form\FormInterface;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('Onboarding/Step4Confirmation')]
class Step4Confirmation
{
    public array $userData = [];
    public bool $termsAccepted = false;
    public ?FormInterface $form = null;
    public array $errors = [];
}
