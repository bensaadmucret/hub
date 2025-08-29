<?php

namespace App\Twig\Components\Onboarding;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('Onboarding/PaymentStep')]
class PaymentStep
{
    public string $price = '49.99';
    public string $priceId;
    public string $customerEmail;
    public string $customerName;
}
