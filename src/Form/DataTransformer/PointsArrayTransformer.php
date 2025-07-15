<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class PointsArrayTransformer implements DataTransformerInterface
{
    public function transform(mixed $value): mixed
    {
        // Transforme le tableau en texte multi-ligne pour le Textarea
        return is_array($value) ? implode(PHP_EOL, $value) : $value;
    }

    public function reverseTransform(mixed $value): mixed
    {
        // Transforme le texte multi-ligne en tableau (ignore les lignes vides)
        return array_filter(array_map('trim', explode(PHP_EOL, $value)));
    }
}
