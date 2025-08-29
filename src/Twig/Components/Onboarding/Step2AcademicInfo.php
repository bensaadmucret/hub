<?php

namespace App\Twig\Components\Onboarding;

use Symfony\Component\Form\FormInterface;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('Onboarding/Step2AcademicInfo')]
class Step2AcademicInfo
{
    public ?string $studyYear = null;
    public ?string $examDate = null;
    public ?FormInterface $form = null;
    public array $errors = [];
}
