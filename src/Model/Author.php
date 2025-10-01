<?php

declare(strict_types=1);

namespace DvidsApi\Model;

/**
 * Represents an author of submitted content who will receive credit for publishing media assets
 */
readonly class Author
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $visionId,
        public ?JobGrade $jobGrade = null,
        /** @var ServiceUnitReference[] */
        public array $serviceUnits = []
    ) {
    }

    /**
     * Create Author from API response data
     */
    public static function fromArray(array $data): self
    {
        $attributes = $data['attributes'] ?? [];
        $relationships = $data['relationships'] ?? [];
        
        $jobGrade = null;
        if (isset($attributes['job_grade'])) {
            $jobGrade = JobGrade::fromArray($attributes['job_grade']);
        }
        
        $serviceUnits = [];
        if (isset($relationships['service_units']['data']) && is_array($relationships['service_units']['data'])) {
            foreach ($relationships['service_units']['data'] as $unit) {
                $serviceUnits[] = new ServiceUnitReference(
                    $unit['id'],
                    $unit['type']
                );
            }
        }
        
        return new self(
            id: $data['id'],
            name: $attributes['name'],
            visionId: $attributes['vision_id'] ?? null,
            jobGrade: $jobGrade,
            serviceUnits: $serviceUnits
        );
    }

    /**
     * Convert to array format for API requests
     */
    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'type' => 'author',
            'attributes' => [
                'name' => $this->name,
                'vision_id' => $this->visionId
            ]
        ];

        if ($this->jobGrade !== null) {
            $data['attributes']['job_grade'] = $this->jobGrade->toArray();
        }

        if (!empty($this->serviceUnits)) {
            $data['relationships']['service_units']['data'] = array_map(
                fn(ServiceUnitReference $unit): array => $unit->toArray(),
                $this->serviceUnits
            );
        }

        return $data;
    }
}