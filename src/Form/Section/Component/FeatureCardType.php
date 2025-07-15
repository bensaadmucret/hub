<?php

namespace App\Form\Section\Component;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FeatureCardType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('icon', TextType::class, [
                'label' => 'Icône (ex: "fas fa-cogs")',
                'help' => 'Utilisez les classes FontAwesome.',
            ])
            ->add('title', TextType::class, [
                'label' => 'Titre de la carte',
            ])
            ->add('points', TextareaType::class, [
                'label' => 'Points clés',
                'help' => 'Un point par ligne.',
            ]);

        // Ajout du DataTransformer pour convertir Textarea <-> array
        $builder->get('points')->addModelTransformer(new \App\Form\DataTransformer\PointsArrayTransformer());
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('data_class', null);
    }
}
