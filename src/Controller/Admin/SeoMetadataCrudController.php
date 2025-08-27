<?php

namespace App\Controller\Admin;

use App\Entity\Seo\SeoMetadata;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * @extends AbstractCrudController<SeoMetadata>
 */
class SeoMetadataCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return SeoMetadata::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id');
        yield TextField::new('title');
        yield TextEditorField::new('description');
        yield ArrayField::new('keywords')->onlyOnIndex();
        yield TextField::new('keywordsAsString', 'Keywords')->onlyOnForms()->setHelp('Mots-clés séparés par des virgules.');
        yield TextField::new('canonicalUrl');
    }
}
