<?php

declare(strict_types=1);

namespace DvidsApi\Model;

use DateTimeImmutable;

/**
 * A publication issue is a collection of metadata and a PDF file submitted together for approval and publishing
 */
readonly class PublicationIssue
{
    public function __construct(
        public string $id,
        public string $description,
        public DateTimeImmutable $createdAt,
        public ?PublicationIssueStatus $status = null,
        public ?PublicationReference $publication = null,
        public ?BatchUploadReference $batchUpload = null
    ) {
    }

    /**
     * Create PublicationIssue from API response data
     */
    public static function fromArray(array $data): self
    {
        $attributes = $data['attributes'] ?? [];
        $relationships = $data['relationships'] ?? [];
        
        $createdAt = new DateTimeImmutable($attributes['created_at']);
        
        $status = null;
        if (isset($attributes['status'])) {
            $status = PublicationIssueStatus::from($attributes['status']);
        }
        
        $publication = null;
        if (isset($relationships['publication']['data'])) {
            $pub = $relationships['publication']['data'];
            $publication = new PublicationReference($pub['id'], $pub['type']);
        }
        
        $batchUpload = null;
        if (isset($relationships['batch_upload']['data'])) {
            $upload = $relationships['batch_upload']['data'];
            $batchUpload = new BatchUploadReference($upload['id'], $upload['type']);
        }
        
        return new self(
            id: $data['id'],
            description: $attributes['description'],
            createdAt: $createdAt,
            status: $status,
            publication: $publication,
            batchUpload: $batchUpload
        );
    }

    /**
     * Convert to array format for API requests
     */
    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'type' => 'publication-issue',
            'attributes' => [
                'description' => $this->description,
                'created_at' => $this->createdAt->format('c')
            ]
        ];
        
        $relationships = [];
        
        if ($this->publication !== null) {
            $relationships['publication']['data'] = $this->publication->toArray();
        }
        
        if ($this->batchUpload !== null) {
            $relationships['batch_upload']['data'] = $this->batchUpload->toArray();
        }
        
        if (!empty($relationships)) {
            $data['relationships'] = $relationships;
        }
        
        return $data;
    }
}