<?php

namespace App\Controller\Admin;

use App\Entity\Page;
use App\Form\SectionType;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class PageCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Page::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('title', 'Titre de la page'),
            SlugField::new('slug', 'URL')->setTargetFieldName('title'),
            TextField::new('metaTitle', 'Titre SEO'),
            TextareaField::new('metaDescription', 'Description SEO'),
            
            // C'est ici que la magie opère
            CollectionField::new('sections', 'Sections de la page')
                ->setEntryType(SectionType::class) // On dit à EasyAdmin d'utiliser notre formulaire parent
                ->setEntryIsComplex(true) // Nécessaire car notre formulaire est dynamique
                ->setFormTypeOptions([
                    'by_reference' => false, // Important pour la persistance des données
                ])
                ->setHelp('Ajoutez, supprimez et réorganisez les sections de votre page.'),
        ];
    }
}
