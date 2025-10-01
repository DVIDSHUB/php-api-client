<?php

declare(strict_types=1);

namespace DvidsApi\Model;

/**
 * Reference to a service unit in relationships
 */
readonly class ServiceUnitReference
{
    public function __construct(
        public string $id,
        public string $type = 'service-unit'
    ) {
    }

    /**
     * Convert to array format for API requests
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type
        ];
    }
}