<?php

namespace App\Twig\Components\Onboarding;

use Symfony\Component\Form\FormInterface;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('Onboarding/Step3StudyObjectives')]
class Step3StudyObjectives
{
    public ?int $targetScore = null;
    public ?int $studyHoursPerWeek = null;
    public ?FormInterface $form = null;
    public array $errors = [];
}
