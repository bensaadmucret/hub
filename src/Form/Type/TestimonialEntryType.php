<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TestimonialEntryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('image_url', UrlType::class, [
                'label' => 'URL de la photo',
                'default_protocol' => 'https',
            ])
            ->add('name', TextType::class, [
                'label' => 'Nom de la personne',
            ])
            ->add('title', TextType::class, [
                'label' => 'Titre/Poste',
                'help' => 'Ex: PACES, Paris',
            ])
            ->add('text', TextareaType::class, [
                'label' => 'Texte du témoignage',
            ])
            ->add('rating', IntegerType::class, [
                'label' => 'Note (de 1 à 5)',
                'attr' => [
                    'min' => 1,
                    'max' => 5,
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Pas de data_class car on mappe à un tableau dans le JSON
        ]);
    }
}
