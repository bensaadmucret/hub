<?php

namespace App\Controller\Admin;

use App\Entity\Contact;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;

class ContactCrudController extends AbstractCrudController
{
    public function __construct(
        private AdminUrlGenerator $adminUrlGenerator
    ) {
    }
    public static function getEntityFqcn(): string
    {
        return Contact::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Contact')
            ->setEntityLabelInPlural('Contacts')
            ->setPageTitle('index', 'Gestion des contacts')
            ->setPageTitle('detail', fn (Contact $contact) => sprintf('Détail du contact #%s', $contact->getId()))
            ->setDefaultSort(['submittedAt' => 'DESC'])
            ->setSearchFields(['name', 'email', 'subject', 'message'])
            ->setEntityPermission('ROLE_ADMIN')
            ->showEntityActionsInlined()
            ->setPaginatorPageSize(30)
            ->overrideTemplate('crud/index', 'admin/contact/index.html.twig');
    }

    public function configureActions(Actions $actions): Actions
    {
        // Désactiver uniquement les actions non nécessaires
        return $actions->disable(Action::NEW, Action::EDIT);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')
                ->hideOnForm(),
            TextField::new('name', 'Nom')
                ->setTemplatePath('admin/fields/contact_name.html.twig')
                ->formatValue(function ($value, $entity) {
                    $url = $this->adminUrlGenerator
                        ->setController(self::class)
                        ->setAction('detail')
                        ->setEntityId($entity->getId())
                        ->generateUrl();
                    
                    return sprintf('<a href="%s">%s</a>', $url, $value);
                }),
            EmailField::new('email', 'Email')
                ->setTemplatePath('admin/fields/email.html.twig'),
            TelephoneField::new('phone', 'Téléphone')
                ->hideOnIndex(),
            TextField::new('subject', 'Sujet')
                ->setMaxLength(50),
            TextareaField::new('message', 'Message')
                ->hideOnIndex()
                ->setMaxLength(1000)
                ->setHelp('Maximum 1000 caractères'),
            DateTimeField::new('submittedAt', 'Date de soumission')
                ->setFormat('dd/MM/yyyy HH:mm')
                ->setSortable(true),
            BooleanField::new('consent', 'Consentement')
                ->renderAsSwitch(false)
                ->setHelp('A accepté la politique de confidentialité'),
        ];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('name', 'Nom')
                ->setFormTypeOption('value_type_options', [
                    'attr' => [
                        'data-filter-ignore-case' => 'true'
                    ]
                ])
            )
            ->add(TextFilter::new('email', 'Email')
                ->setFormTypeOption('value_type_options', [
                    'attr' => [
                        'data-filter-ignore-case' => 'true'
                    ]
                ])
            )
            ->add(TextFilter::new('subject', 'Sujet')
                ->setFormTypeOption('value_type_options', [
                    'attr' => [
                        'data-filter-ignore-case' => 'true'
                    ]
                ])
            )
            ->add(DateTimeFilter::new('submittedAt', 'Date de soumission'))
            ->add(BooleanFilter::new('consent', 'A donné son consentement'));
    }
}
