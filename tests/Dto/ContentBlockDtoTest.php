<?php

declare(strict_types=1);

namespace App\Tests\Dto;

use App\Dto\ContentBlockDto;
use App\Entity\Seo\BlockType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ContentBlockDtoTest extends KernelTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = self::getContainer()->get(ValidatorInterface::class);
    }

    public function testHeroRequiresTitle(): void
    {
        $dto = new ContentBlockDto();
        $dto->type = BlockType::HERO;
        $dto->config = [
            // 'title' missing on purpose
            'subtitle' => 'Sub',
        ];

        $violations = $this->validator->validate($dto);
        $this->assertGreaterThan(0, $violations->count(), 'Expected at least one violation for missing title');
        $messages = [];
        foreach ($violations as $v) {
            $messages[] = $v->getPropertyPath();
        }
        $this->assertContains('config[title]', $messages);
    }

    public function testFeaturesGridRequiresTitleAndFeatures(): void
    {
        $dto = new ContentBlockDto();
        $dto->type = BlockType::FEATURES_GRID;
        $dto->config = [
            // 'title' and 'features' are missing
        ];

        $violations = $this->validator->validate($dto);
        $this->assertGreaterThanOrEqual(2, $violations->count());
        $paths = array_map(static fn($v) => $v->getPropertyPath(), iterator_to_array($violations));
        $this->assertContains('config[title]', $paths);
        $this->assertContains('config[features]', $paths);
    }

    public function testTestimonialsGridRequiresTitleAndTestimonials(): void
    {
        $dto = new ContentBlockDto();
        $dto->type = BlockType::TESTIMONIALS_GRID;
        $dto->config = [];

        $violations = $this->validator->validate($dto);
        $this->assertGreaterThanOrEqual(2, $violations->count());
        $paths = array_map(static fn($v) => $v->getPropertyPath(), iterator_to_array($violations));
        $this->assertContains('config[title]', $paths);
        $this->assertContains('config[testimonials]', $paths);
    }
}
