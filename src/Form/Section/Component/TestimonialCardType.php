<?php

namespace App\Form\Section\Component;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TestimonialCardType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('author_name', TextType::class, [
                'label' => 'Nom de l\'auteur',
            ])
            ->add('author_role', TextType::class, [
                'label' => 'Rôle ou entreprise de l\'auteur',
                'required' => false,
            ])
            ->add('text', TextareaType::class, [
                'label' => 'Texte du témoignage',
            ])
            ->add('rating', ChoiceType::class, [
                'label' => 'Note (sur 5)',
                'choices' => [
                    '1 étoile' => 1,
                    '2 étoiles' => 2,
                    '3 étoiles' => 3,
                    '4 étoiles' => 4,
                    '5 étoiles' => 5,
                ],
                'placeholder' => 'Choisir une note',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('data_class', null);
    }
}
