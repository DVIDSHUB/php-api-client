<?php

declare(strict_types=1);

namespace DvidsApi\Model;

/**
 * Represents a unit of the armed services. Each unit has exactly one branch attribute.
 */
readonly class ServiceUnit
{
    public function __construct(
        public string $id,
        public string $name,
        public string $abbreviation,
        public Branch $branch,
        public ?string $dvian = null,
        public bool $requiresPublishingApproval = false
    ) {
    }

    /**
     * Create ServiceUnit from API response data
     */
    public static function fromArray(array $data): self
    {
        $attributes = $data['attributes'] ?? [];
        
        $branch = Branch::from($attributes['branch']);
        
        return new self(
            id: $data['id'],
            name: $attributes['name'],
            abbreviation: $attributes['abbreviation'],
            branch: $branch,
            dvian: $attributes['dvian'] ?? null,
            requiresPublishingApproval: $attributes['requires_publishing_approval'] ?? false
        );
    }

    /**
     * Convert to array format for API requests
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => 'service-unit',
            'attributes' => [
                'name' => $this->name,
                'abbreviation' => $this->abbreviation,
                'branch' => $this->branch->value,
                'dvian' => $this->dvian,
                'requires_publishing_approval' => $this->requiresPublishingApproval
            ]
        ];
    }
}