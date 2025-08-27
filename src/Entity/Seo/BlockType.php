<?php

declare(strict_types=1);

namespace App\Entity\Seo;

/**
 * Enumeration stricte des types de blocs de contenu.
 * Les valeurs correspondent aux clés utilisées dans les formulaires et la persistance.
 */
enum BlockType: string
{
    case HERO = 'hero';
    case FEATURES_GRID = 'features_grid';
    case TESTIMONIALS_GRID = 'testimonials_grid';

    /**
     * Libellé utilisateur du type de bloc.
     */
    public function label(): string
    {
        return match ($this) {
            self::HERO => 'Hero',
            self::FEATURES_GRID => 'Grille de fonctionnalités',
            self::TESTIMONIALS_GRID => 'Grille de témoignages',
        };
    }
}
