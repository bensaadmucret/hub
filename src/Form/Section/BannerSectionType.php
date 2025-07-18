<?php

namespace App\Form\Section;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BannerSectionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre principal',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Votre Coach Intelligent pour Réussir en Médecine'
                ]
            ])
            ->add('subtitle', TextareaType::class, [
                'label' => 'Sous-titre',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Combinaison unique d\'IA avancée, coaching personnalisé...',
                    'rows' => 3
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('data_class', null);
    }
}
