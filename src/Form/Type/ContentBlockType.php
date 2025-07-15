<?php

namespace App\Form\Type;

use App\Entity\Seo\BlockType;
use App\Entity\Seo\ContentBlock;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContentBlockType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', EnumType::class, [
                'class' => BlockType::class,
                'label' => 'Type de Bloc',
                'choice_label' => function (BlockType $choice) {
                    return $choice->label();
                },
                'attr' => [
                    'data-content-block-form-target' => 'typeSelect',
                    'data-action' => 'change->content-block-form#toggle'
                ],
            ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData']);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'onPreSubmit']);
    }

    public function onPreSetData(FormEvent $event): void
    {
        $data = $event->getData();
        $form = $event->getForm();

        if (null === $data || null === $data->getType()) {
            return;
        }

        $this->addConfigFields($form, $data->getType());
    }

    public function onPreSubmit(FormEvent $event): void
    {
        $data = $event->getData();
        $form = $event->getForm();

        $typeValue = $data['type'] ?? null;
        if (!$typeValue) {
            return;
        }

        $type = BlockType::from($typeValue);
        $this->addConfigFields($form, $type);
    }

    private function addConfigFields(FormInterface $form, BlockType $type): void
    {
        $this->addFieldsContainer($form, BlockType::HERO, function (FormInterface $form) {
            $form
                ->add('title', TextType::class, ['label' => 'Titre du Hero', 'property_path' => 'config[title]'])
                ->add('subtitle', TextareaType::class, ['label' => 'Sous-titre', 'property_path' => 'config[subtitle]', 'required' => false])
                ->add('button_text', TextType::class, ['label' => 'Texte du Bouton', 'property_path' => 'config[button_text]', 'required' => false])
                ->add('button_link', TextType::class, ['label' => 'Lien du Bouton', 'property_path' => 'config[button_link]', 'required' => false])
                ->add('image_url', TextType::class, ['label' => 'URL de l\'image', 'property_path' => 'config[image_url]', 'required' => false]);
        });

        $this->addFieldsContainer($form, BlockType::FEATURES_GRID, function (FormInterface $form) {
            $form
                ->add('title', TextType::class, ['label' => 'Titre de la section', 'property_path' => 'config[title]'])
                ->add('subtitle', TextareaType::class, ['label' => 'Sous-titre de la section', 'property_path' => 'config[subtitle]', 'required' => false])
                ->add('features', CollectionType::class, [
                    'label' => 'Cartes de fonctionnalité',
                    'entry_type' => FeatureEntryType::class,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'by_reference' => false,
                    'property_path' => 'config[features]',
                ]);
        });

        $this->addFieldsContainer($form, BlockType::TESTIMONIALS_GRID, function (FormInterface $form) {
            $form
                ->add('title', TextType::class, ['label' => 'Titre de la section', 'property_path' => 'config[title]'])
                ->add('subtitle', TextareaType::class, ['label' => 'Sous-titre de la section', 'property_path' => 'config[subtitle]', 'required' => false])
                ->add('testimonials', CollectionType::class, [
                    'label' => 'Témoignages',
                    'entry_type' => TestimonialEntryType::class,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'by_reference' => false,
                    'property_path' => 'config[testimonials]',
                ]);
        });
    }

    private function addFieldsContainer(FormInterface $form, BlockType $type, callable $callback): void
    {
        $childForm = $form->getConfig()->getFormFactory()->createNamedBuilder($type->value, FormType::class, null, [
            'auto_initialize' => false,
            'label' => false,
            'attr' => [
                'data-content-block-form-target' => 'configFieldsWrapper',
                'data-block-type' => $type->value,
            ],
        ])->getForm();

        $callback($childForm);

        $form->add($childForm);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => ContentBlock::class]);
    }
}
