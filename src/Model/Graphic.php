<?php

declare(strict_types=1);

namespace DvidsApi\Model;

use DateTimeImmutable;

/**
 * A graphic is a collection of metadata and an uploaded file submitted together for approval and publishing.
 * Supports file types: JPG, PNG, PDF, INDD, PSD, AI, MP4, Zip containing PDF and other files
 */
readonly class Graphic
{
    public function __construct(
        public string $id,
        public string $title,
        public string $description,
        public string $instructions,
        public DateTimeImmutable $createdAt,
        public string $virin,
        public string $country,
        /** @var string[] */
        public array $tags = [],
        public ?string $subdiv = null,
        public ?string $city = null,
        public ?GraphicStatus $status = null,
        public ?string $captionWriter = null,
        /** @var AuthorReference[] */
        public array $authors = [],
        public ?BatchUploadReference $batchUpload = null,
        public ?GraphicCategoryReference $category = null,
        public ?ServiceUnitReference $serviceUnit = null,
        /** @var ThemeReference[] */
        public array $themes = []
    ) {
    }

    /**
     * Create Graphic from API response data
     */
    public static function fromArray(array $data): self
    {
        $attributes = $data['attributes'] ?? [];
        $relationships = $data['relationships'] ?? [];
        
        $createdAt = new DateTimeImmutable($attributes['created_at']);
        
        $status = null;
        if (isset($attributes['status'])) {
            $status = GraphicStatus::from($attributes['status']);
        }
        
        $authors = [];
        if (isset($relationships['authors']['data']) && is_array($relationships['authors']['data'])) {
            foreach ($relationships['authors']['data'] as $author) {
                $authors[] = new AuthorReference($author['id'], $author['type']);
            }
        }
        
        $batchUpload = null;
        if (isset($relationships['batch_upload']['data'])) {
            $upload = $relationships['batch_upload']['data'];
            $batchUpload = new BatchUploadReference($upload['id'], $upload['type']);
        }
        
        $category = null;
        if (isset($relationships['category']['data'])) {
            $cat = $relationships['category']['data'];
            $category = new GraphicCategoryReference($cat['id'], $cat['type']);
        }
        
        $serviceUnit = null;
        if (isset($relationships['service_unit']['data'])) {
            $unit = $relationships['service_unit']['data'];
            $serviceUnit = new ServiceUnitReference($unit['id'], $unit['type']);
        }
        
        $themes = [];
        if (isset($relationships['themes']['data']) && is_array($relationships['themes']['data'])) {
            foreach ($relationships['themes']['data'] as $theme) {
                $themes[] = new ThemeReference($theme['id'], $theme['type']);
            }
        }
        
        return new self(
            id: $data['id'],
            title: $attributes['title'],
            description: $attributes['description'],
            instructions: $attributes['instructions'],
            createdAt: $createdAt,
            virin: $attributes['virin'],
            country: $attributes['country'],
            tags: $attributes['tags'] ?? [],
            subdiv: $attributes['subdiv'] ?? null,
            city: $attributes['city'] ?? null,
            status: $status,
            captionWriter: $attributes['caption_writer'] ?? null,
            authors: $authors,
            batchUpload: $batchUpload,
            category: $category,
            serviceUnit: $serviceUnit,
            themes: $themes
        );
    }

    /**
     * Convert to array format for API requests
     */
    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'type' => 'graphic',
            'attributes' => [
                'title' => $this->title,
                'description' => $this->description,
                'instructions' => $this->instructions,
                'created_at' => $this->createdAt->format('c'),
                'virin' => $this->virin,
                'country' => $this->country,
                'tags' => $this->tags
            ]
        ];
        
        if ($this->subdiv !== null) {
            $data['attributes']['subdiv'] = $this->subdiv;
        }
        
        if ($this->city !== null) {
            $data['attributes']['city'] = $this->city;
        }
        
        if ($this->captionWriter !== null) {
            $data['attributes']['caption_writer'] = $this->captionWriter;
        }
        
        $relationships = [];
        
        if (!empty($this->authors)) {
            $relationships['authors']['data'] = array_map(
                fn(AuthorReference $author): array => $author->toArray(),
                $this->authors
            );
        }
        
        if ($this->batchUpload !== null) {
            $relationships['batch_upload']['data'] = $this->batchUpload->toArray();
        }
        
        if ($this->category !== null) {
            $relationships['category']['data'] = $this->category->toArray();
        }
        
        if ($this->serviceUnit !== null) {
            $relationships['service_unit']['data'] = $this->serviceUnit->toArray();
        }
        
        if (!empty($this->themes)) {
            $relationships['themes']['data'] = array_map(
                fn(ThemeReference $theme): array => $theme->toArray(),
                $this->themes
            );
        }
        
        if (!empty($relationships)) {
            $data['relationships'] = $relationships;
        }
        
        return $data;
    }
}