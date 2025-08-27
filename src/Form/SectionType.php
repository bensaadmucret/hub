<?php

namespace App\Form;

use App\Entity\Section;
use App\Form\Section\AdvantagesSectionType;
use App\Form\Section\FeaturesSectionType;
use App\Form\Section\ContactSectionType;
use App\Form\Section\CtaSectionType;
use App\Form\Section\HeroSectionType;
use App\Form\Section\TestimonialsSectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use App\Form\Section\BannerSectionType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SectionType extends AbstractType
{
    // On centralise ici les types de sections disponibles.
    // Il sera facile d'en ajouter d'autres plus tard.
    private const SECTION_TYPES = [
        'Section Hero' => 'hero',
        'Section Fonctionnalités' => 'features',
        'Section Avantages' => 'advantages',
        'Section Témoignages' => 'testimonials',
        'Section CTA' => 'cta',
        'Section Banner' => 'banner',
        'Section Contact' => 'contact',
    ];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'label' => 'Type de section',
                'choices' => self::SECTION_TYPES,
                'placeholder' => 'Choisir un type de section...',
            ]);

        // Cette fonction écoute les événements du formulaire pour ajouter
        // le bon champ "content" en fonction du type de section choisi.
        $formModifier = function (FormInterface $form, ?string $type) {
            if ($type === null) {
                return;
            }

            switch ($type) {
                case 'hero':
                    $form->add('content', HeroSectionType::class, [
                        'label' => 'Contenu de la section Hero',
                    ]);
                    break;
                case 'features':
                    $form->add('content', FeaturesSectionType::class, [
                        'label' => 'Contenu de la section Fonctionnalités',
                    ]);
                    break;
                case 'advantages':
                    $form->add('content', AdvantagesSectionType::class, [
                        'label' => 'Contenu de la section Avantages',
                    ]);
                    break;
                case 'testimonials':
                    $form->add('content', TestimonialsSectionType::class, [
                        'label' => 'Contenu de la section Témoignages',
                    ]);
                    break;
                case 'cta':
                    $form->add('content', CtaSectionType::class, [
                        'label' => 'Contenu de la section CTA',
                    ]);
                    break;
                case 'banner':
                    $form->add('content', BannerSectionType::class, [
                        'label' => 'Contenu de la section Banner',
                    ]);
                    break;
                case 'contact':
                    $form->add('content', ContactSectionType::class, [
                        'label' => 'Contenu de la section Contact',
                    ]);
                    break;
                // On ajoutera ici les 'case' pour les autres types de section
            }
        };

        // Événement pour le chargement initial du formulaire (ex: page d'édition)
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($formModifier) {
                $section = $event->getData();
                $type = null;
                if ($section instanceof Section) {
                    $candidate = $section->getType();
                    $type = is_string($candidate) ? $candidate : null;
                }
                $formModifier($event->getForm(), $type);
            }
        );

        // Événement pour la soumission du formulaire (ex: création ou changement de type)
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use ($formModifier) {
                $data = $event->getData();
                $type = null;
                if (is_array($data)) {
                    $candidate = $data['type'] ?? null;
                    $type = is_string($candidate) ? $candidate : null;
                }
                $formModifier($event->getForm(), $type);
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Section::class,
        ]);
    }
}
