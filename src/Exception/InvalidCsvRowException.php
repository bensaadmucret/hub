<?php

declare(strict_types=1);

namespace App\Exception;

class InvalidCsvRowException extends \RuntimeException
{
    /** @var array<string, string[]> */
    private array $validationErrors;

    /**
     * @param array<string, string[]> $validationErrors
     */
    public function __construct(string $message = '', array $validationErrors = [], int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->validationErrors = $validationErrors;
    }

    /**
     * @return array<string, string[]> Tableau des erreurs de validation par champ
     */
    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }
}
