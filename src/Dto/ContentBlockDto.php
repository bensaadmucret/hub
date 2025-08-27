<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\Seo\BlockType;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * DTO de formulaire pour un bloc de contenu générique.
 * Conserve une structure flexible via `config` (array),
 * avec validations conditionnelles selon `type`.
 */
class ContentBlockDto
{
    /**
     * Type de bloc sélectionné.
     */
    public ?BlockType $type = null;

    /**
     * Données de configuration spécifiques au type.
     * @var array<string, mixed>
     */
    public array $config = [];

    /**
     * Validation conditionnelle en fonction du type.
     *
     * @param ExecutionContextInterface $context
     */
    #[Assert\Callback]
    public function validateByType(ExecutionContextInterface $context): void
    {
        if (!$this->type instanceof BlockType) {
            return; // le champ type gère déjà sa propre validation
        }

        $path = 'config';
        $cfg = $this->config;

        $requireNonEmptyString = static function (string $key) use ($cfg, $context, $path): void {
            $val = $cfg[$key] ?? null;
            if (!is_string($val) || trim($val) === '') {
                $context->buildViolation('Ce champ est requis.')
                    ->atPath($path . '[' . $key . ']')
                    ->addViolation();
            }
        };

        $requireNonEmptyArray = static function (string $key) use ($cfg, $context, $path): void {
            $val = $cfg[$key] ?? null;
            if (!is_array($val) || count($val) === 0) {
                $context->buildViolation('Cette liste ne peut pas être vide.')
                    ->atPath($path . '[' . $key . ']')
                    ->addViolation();
            }
        };

        switch ($this->type) {
            case BlockType::HERO:
                $requireNonEmptyString('title');
                // subtitle/button_text/button_link/image_url sont optionnels
                break;

            case BlockType::FEATURES_GRID:
                $requireNonEmptyString('title');
                $requireNonEmptyArray('features');
                break;

            case BlockType::TESTIMONIALS_GRID:
                $requireNonEmptyString('title');
                $requireNonEmptyArray('testimonials');
                break;
        }
    }
}
