<?php

declare(strict_types=1);

namespace DvidsApi\Model;

use DateTimeImmutable;

/**
 * A publication is a collection of related publication issues published together
 */
readonly class Publication
{
    public function __construct(
        public string $id,
        public string $title,
        public string $description,
        public DateTimeImmutable $createdAt,
        public ?ServiceUnitReference $serviceUnit = null
    ) {
    }

    /**
     * Create Publication from API response data
     */
    public static function fromArray(array $data): self
    {
        $attributes = $data['attributes'] ?? [];
        $relationships = $data['relationships'] ?? [];
        
        $createdAt = new DateTimeImmutable($attributes['created_at']);
        
        $serviceUnit = null;
        if (isset($relationships['service_unit']['data'])) {
            $unit = $relationships['service_unit']['data'];
            $serviceUnit = new ServiceUnitReference($unit['id'], $unit['type']);
        }
        
        return new self(
            id: $data['id'],
            title: $attributes['title'],
            description: $attributes['description'],
            createdAt: $createdAt,
            serviceUnit: $serviceUnit
        );
    }

    /**
     * Convert to array format for API requests
     */
    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'type' => 'publication',
            'attributes' => [
                'title' => $this->title,
                'description' => $this->description,
                'created_at' => $this->createdAt->format('c')
            ]
        ];
        
        if ($this->serviceUnit !== null) {
            $data['relationships']['service_unit']['data'] = $this->serviceUnit->toArray();
        }
        
        return $data;
    }
}