<?php

namespace App\Form\Field;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormInterface;

class ContactFormFieldType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Identifiant technique',
                'help' => 'Doit être unique (ex: email, nom, telephone)',
                'attr' => [
                    'class' => 'field-name',
                    'data-validate' => 'required|alpha_dash|unique_field_name'
                ]
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type de champ',
                'choices' => [
                    'Texte court' => 'text',
                    'Email' => 'email',
                    'Téléphone' => 'tel',
                    'Sélection' => 'select',
                    'Zone de texte' => 'textarea',
                    'Case à cocher' => 'checkbox',
                ],
                'attr' => [
                    'class' => 'field-type',
                    'data-controller' => 'contact-form-field',
                    'data-action' => 'change->contact-form-field#onTypeChange'
                ]
            ])
            ->add('label', TextType::class, [
                'label' => 'Libellé',
                'attr' => [
                    'data-validate' => 'required'
                ]
            ])
            ->add('placeholder', TextType::class, [
                'label' => 'Texte indicatif',
                'required' => false,
                'help' => 'Texte affiché en gris dans le champ quand il est vide'
            ])
            ->add('required', CheckboxType::class, [
                'label' => 'Champ obligatoire',
                'required' => false,
                'label_attr' => ['class' => 'checkbox-label'],
                'attr' => [
                    'data-action' => 'change->form#onRequiredChange'
                ]
            ]);

        // Gestion des champs conditionnels
        $addConditionalFields = function (FormEvent $event) {
            $form = $event->getForm();
            $data = $event->getData();
            if (!is_array($data)) {
                return;
            }
            $isSelectType = isset($data['type']) && $data['type'] === 'select';
            $isCheckboxType = isset($data['type']) && $data['type'] === 'checkbox';

            // Gestion des options pour le type select
            if ($isSelectType) {
                $options = [
                    'entry_type' => TextType::class,
                    'label' => 'Options de sélection',
                    'allow_add' => true,
                    'allow_delete' => true,
                    'delete_empty' => true,
                    'prototype' => true,
                    'required' => false,
                    'entry_options' => [
                        'label' => false,
                        'attr' => [
                            'class' => 'form-control mb-1',
                            'placeholder' => 'Option',
                            'data-form-field-target' => 'optionInput'
                        ]
                    ],
                    'attr' => [
                        'class' => 'select-options',
                        'data-form-field-target' => 'optionsContainer',
                        'data-controller' => 'contact-form-field'
                    ],
                    'prototype_name' => '__option_prototype__',
                    'block_name' => 'select_options',
                    'by_reference' => false,
                    'row_attr' => [
                        'class' => 'select-options-container'
                    ]
                ];

                // Si des options existent déjà, on les ajoute
                if (isset($data['options']) && is_array($data['options'])) {
                    $options['data'] = $data['options'];
                }

                $form->add('options', CollectionType::class, $options);
            }

            // Configuration spécifique pour les checkbox
            if ($isCheckboxType) {
                $form->add('default_checked', CheckboxType::class, [
                    'label' => 'Coché par défaut',
                    'required' => false,
                    'label_attr' => ['class' => 'checkbox-label']
                ]);

                $form->add('consent_text', TextType::class, [
                    'label' => 'Texte de consentement',
                    'required' => false,
                    'help' => 'Texte affiché à côté de la case à cocher',
                    'attr' => [
                        'placeholder' => 'Ex: J\'accepte les conditions d\'utilisation',
                        'data-validate' => 'required'
                    ]
                ]);
            }
        };

        // Ajout des écouteurs d'événements
        $builder->addEventListener(FormEvents::PRE_SET_DATA, $addConditionalFields);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, $addConditionalFields);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'allow_extra_fields' => true,
            'attr' => [
                'class' => 'contact-field-form',
                'data-controller' => 'contact-form-field',
                'data-action' => 'change->contact-form-field#onTypeChange'
            ]
        ]);
    }
}
