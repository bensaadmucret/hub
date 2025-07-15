<?php

namespace App\Form\Section;

use App\Form\Section\Component\FeatureCardType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FeaturesSectionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('main_title', TextType::class, [
                'label' => 'Titre de la section',
            ])
            ->add('main_subtitle', TextareaType::class, [
                'label' => 'Sous-titre de la section',
                'required' => false,
            ])
            ->add('cards', CollectionType::class, [
                'label' => 'Cartes de fonctionnalités',
                'entry_type' => FeatureCardType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype' => true,
                'entry_options' => ['label' => false],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('data_class', null);
    }
}
