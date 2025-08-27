<?php

namespace App\Entity\Seo;

use App\Repository\SeoMetadataRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SeoMetadataRepository::class)]
class SeoMetadata
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    /** @phpstan-ignore-next-line Doctrine assigns the ID at runtime */
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $title = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    /**
     * @var array<int, string>
     */
    #[ORM\Column(type: 'json', nullable: true)]
    private array $keywords = [];

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $canonicalUrl = null;

    // Getters and setters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return array<int, string>
     */
    public function getKeywords(): array
    {
        return $this->keywords;
    }

    /**
     * @param array<int, string|int|float|bool|null> $keywords
     */
    public function setKeywords(array $keywords): self
    {
        // Normalize to array<int, string> and drop empty entries
        $normalized = array_values(array_filter(array_map(static function ($v): string {
            return (string) $v;
        }, $keywords), static function (string $v): bool {
            return $v !== '';
        }));
        $this->keywords = $normalized;
        return $this;
    }

    public function getCanonicalUrl(): ?string
    {
        return $this->canonicalUrl;
    }

    public function setCanonicalUrl(?string $canonicalUrl): self
    {
        $this->canonicalUrl = $canonicalUrl;
        return $this;
    }
}
