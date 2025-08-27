<?php

declare(strict_types=1);

namespace App\Tests\Form;

use App\Form\Type\ContentBlockType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

class ContentBlockTypeTest extends KernelTestCase
{
    private FormFactoryInterface $forms;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->forms = self::getContainer()->get(FormFactoryInterface::class);
    }

    public function testHeroValidWhenTitleProvided(): void
    {
        $form = $this->forms->create(ContentBlockType::class, null, [
            'csrf_protection' => false,
        ]);
        $form->submit([
            'type' => 'hero',
            'hero' => [
                'title' => 'Titre',
                // others optional
            ],
        ]);

        $this->assertTrue($form->isSubmitted());
        $dtoDump = $this->dumpFormData($form);
        $this->assertTrue($form->isValid(), 'Hero should be valid with title. Errors: '.$this->dumpFormErrors($form).' Data: '.$dtoDump);
    }

    public function testHeroInvalidWithoutTitle(): void
    {
        $form = $this->forms->create(ContentBlockType::class, null, [
            'csrf_protection' => false,
        ]);
        $form->submit([
            'type' => 'hero',
            'hero' => [
                // missing title on purpose
            ],
        ]);

        $this->assertTrue($form->isSubmitted());
        $this->assertFalse($form->isValid(), 'Hero should be invalid without title');
    }

    public function testFeaturesGridRequiresTitleAndAtLeastOneFeature(): void
    {
        $form = $this->forms->create(ContentBlockType::class, null, [
            'csrf_protection' => false,
        ]);
        $form->submit([
            'type' => 'features_grid',
            'features_grid' => [
                'title' => 'Section title',
                'features' => [
                    [
                        'icon' => 'fa fa-star',
                        'title' => 'Feature 1',
                        'items' => ['Point A', 'Point B'],
                    ],
                ],
            ],
        ]);

        $this->assertTrue($form->isSubmitted());
        $dtoDump = $this->dumpFormData($form);
        $this->assertTrue($form->isValid(), 'Features grid should be valid with title and one feature. Errors: '.$this->dumpFormErrors($form).' Data: '.$dtoDump);
    }

    public function testFeaturesGridInvalidWithoutFeatures(): void
    {
        $form = $this->forms->create(ContentBlockType::class, null, [
            'csrf_protection' => false,
        ]);
        $form->submit([
            'type' => 'features_grid',
            'features_grid' => [
                'title' => 'Section title',
                // missing features
            ],
        ]);

        $this->assertTrue($form->isSubmitted());
        $this->assertFalse($form->isValid(), 'Features grid should be invalid without features');
    }

    public function testTestimonialsGridRequiresTitleAndAtLeastOneTestimonial(): void
    {
        $form = $this->forms->create(ContentBlockType::class, null, [
            'csrf_protection' => false,
        ]);
        $form->submit([
            'type' => 'testimonials_grid',
            'testimonials_grid' => [
                'title' => 'Clients',
                'testimonials' => [
                    [
                        'image_url' => 'https://example.com/p.jpg',
                        'name' => 'Alice',
                        'title' => 'Etudiante',
                        'text' => 'Super !',
                        'rating' => 5,
                    ],
                ],
            ],
        ]);

        $this->assertTrue($form->isSubmitted());
        $dtoDump = $this->dumpFormData($form);
        $this->assertTrue($form->isValid(), 'Testimonials grid should be valid with title and one testimonial. Errors: '.$this->dumpFormErrors($form).' Data: '.$dtoDump);
    }

    public function testTestimonialsGridInvalidWithoutTestimonials(): void
    {
        $form = $this->forms->create(ContentBlockType::class);
        $form->submit([
            'type' => 'testimonials_grid',
            'testimonials_grid' => [
                'title' => 'Clients',
                // missing testimonials
            ],
        ]);

        $this->assertTrue($form->isSubmitted());
        $this->assertFalse($form->isValid(), 'Testimonials grid should be invalid without testimonials');
    }

    private function dumpFormErrors(FormInterface $form): string
    {
        $lines = [];
        foreach ($form->getErrors(true, true) as $error) {
            $origin = $error->getOrigin();
            $path = $origin ? (string) $origin->getPropertyPath() : '';
            $lines[] = sprintf('%s: %s', $path, $error->getMessage());
        }
        return $lines ? implode(' | ', $lines) : '[no errors]';
    }

    private function dumpFormData(FormInterface $form): string
    {
        $data = $form->getData();
        if (\is_object($data) && method_exists($data, '__debugInfo')) {
            return var_export($data, true);
        }
        if (\is_object($data)) {
            // Try to extract minimal info if this is ContentBlockDto
            try {
                $type = property_exists($data, 'type') ? $data->type : null;
                $config = property_exists($data, 'config') ? $data->config : null;
                return sprintf('type=%s; config=%s', (string) $type?->value, var_export($config, true));
            } catch (\Throwable $e) {
                return '[object data: '.get_class($data).']';
            }
        }
        return var_export($data, true);
    }
}
