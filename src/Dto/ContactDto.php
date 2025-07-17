<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class ContactDto
{
    #[Assert\NotBlank]
    public string $name;

    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email;

    #[Assert\Length(max: 20)]
    public ?string $phone = null;

    #[Assert\NotBlank]
    public string $subject;

    #[Assert\NotBlank]
    public string $message;

    #[Assert\IsTrue]
    public bool $consent;
}
