<?php

namespace App\Form\Section;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CtaSectionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
            ])
            ->add('subtitle', TextareaType::class, [
                'label' => 'Sous-titre',
                'required' => false,
            ])
            ->add('button_text', TextType::class, [
                'label' => 'Texte du bouton',
            ])
            ->add('button_link', UrlType::class, [
                'label' => 'Lien du bouton principal',
            ])
            ->add('secondary_button_text', TextType::class, [
                'label' => 'Texte du bouton secondaire',
                'required' => false,
            ])
            ->add('secondary_button_link', UrlType::class, [
                'label' => 'Lien du bouton secondaire',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('data_class', null);
    }
}
