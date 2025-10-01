<?php

declare(strict_types=1);

namespace DvidsApi\Model;

/**
 * Reference to a graphic category in relationships
 */
readonly class GraphicCategoryReference
{
    public function __construct(
        public string $id,
        public string $type = 'graphic-category'
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