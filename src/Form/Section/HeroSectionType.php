<?php

namespace App\Form\Section;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HeroSectionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre principal',
            ])
            ->add('subtitle', TextareaType::class, [
                'label' => 'Sous-titre',
            ])
            ->add('button_text', TextType::class, [
                'label' => 'Texte du bouton',
                'required' => false,
            ])
            ->add('button_link', UrlType::class, [
                'label' => 'Lien du bouton',
                'default_protocol' => 'https',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        // Ce formulaire n'est pas lié à une entité, il gère une partie du tableau "content" de l'entité Section.
        $resolver->setDefault('data_class', null);
    }
}
