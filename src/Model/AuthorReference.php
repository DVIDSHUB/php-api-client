<?php

declare(strict_types=1);

namespace DvidsApi\Model;

/**
 * Reference to an author in relationships
 */
readonly class AuthorReference
{
    public function __construct(
        public string $id,
        public string $type = 'author'
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