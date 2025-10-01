<?php

declare(strict_types=1);

namespace DvidsApi\Model;

/**
 * Reference to a batch upload in relationships
 */
readonly class BatchUploadReference
{
    public function __construct(
        public string $id,
        public string $type
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