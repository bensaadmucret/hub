<?php

declare(strict_types=1);

namespace App\Service\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class ImportRowDto
{
    #[Assert\NotBlank]
    private string $name;

    #[Assert\NotBlank]
    #[Assert\Email]
    private string $email;

    #[Assert\Type(type: 'numeric')]
    private ?string $amount;

    #[Assert\Type(type: 'datetime')]
    private ?string $created_at;

    #[Assert\Type(type: 'datetime')]
    private ?string $updated_at;

    /**
     * @param array{
     *     name?: string,
     *     email?: string,
     *     amount?: string|null,
     *     created_at?: string|null,
     *     updated_at?: string|null
     * } $row Les données de la ligne CSV
     */
    public function __construct(array $row)
    {
        $this->name = $row['name'] ?? '';
        $this->email = $row['email'] ?? '';
        $this->amount = $row['amount'] ?? null;
        $this->created_at = $row['created_at'] ?? null;
        $this->updated_at = $row['updated_at'] ?? null;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function getCreatedAt(): ?string
    {
        return $this->created_at;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updated_at;
    }
}
