<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

/**
 * @implements DataTransformerInterface<array<int, string>|null, string>
 */
class PointsArrayTransformer implements DataTransformerInterface
{
    /**
     * @param array<int, string>|null $value
     */
    public function transform(mixed $value): string
    {
        // Transforme le tableau en texte multi-ligne pour le Textarea
        if ($value === null) {
            return '';
        }
        // According to @implements, $value is array<int, string> here
        return implode(PHP_EOL, $value);
    }

    /**
     * @return array<int, string>
     */
    public function reverseTransform(mixed $value): array
    {
        // Transforme le texte multi-ligne en tableau (ignore les lignes vides)
        $valueStr = is_string($value) ? $value : (string) $value;
        return array_values(array_filter(array_map('trim', explode(PHP_EOL, $valueStr)), static fn(string $v) => $v !== ''));
    }
}
