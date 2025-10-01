<?php

declare(strict_types=1);

namespace DvidsApi\Model;

use DateTimeImmutable;

/**
 * A batch is a collection of files submitted together for approval and publishing
 */
readonly class Batch
{
    public function __construct(
        public string $id,
        public ?DateTimeImmutable $createdAt = null,
        public ?DateTimeImmutable $closedAt = null,
        public bool $closed = false,
        public bool $sendConfirmationEmail = true
    ) {
    }

    /**
     * Create Batch from API response data
     */
    public static function fromArray(array $data): self
    {
        $attributes = $data['attributes'] ?? [];
        
        $createdAt = null;
        if (isset($attributes['created_at'])) {
            $createdAt = new DateTimeImmutable($attributes['created_at']);
        }
        
        $closedAt = null;
        if (isset($attributes['closed_at'])) {
            $closedAt = new DateTimeImmutable($attributes['closed_at']);
        }
        
        return new self(
            id: $data['id'],
            createdAt: $createdAt,
            closedAt: $closedAt,
            closed: $attributes['closed'] ?? false,
            sendConfirmationEmail: $attributes['send_confirmation_email'] ?? true
        );
    }

    /**
     * Convert to array format for API requests
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => 'batch',
            'attributes' => [
                'closed' => $this->closed,
                'send_confirmation_email' => $this->sendConfirmationEmail
            ]
        ];
    }
}