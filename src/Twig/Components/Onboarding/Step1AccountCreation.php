<?php

namespace App\Twig\Components\Onboarding;

use Symfony\Component\Form\FormInterface;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\PostMount;

#[AsTwigComponent('Onboarding/Step1AccountCreation')]
class Step1AccountCreation
{
    public ?string $firstName = null;
    public ?string $lastName = null;
    public ?string $email = null;
    public ?string $password = null;
    public ?string $confirmPassword = null;
    public ?FormInterface $form = null;
    public array $errors = [];
}
