<?php

declare(strict_types=1);

namespace DvidsApi\Model;

/**
 * Represents a batch upload with presigned URLs for file uploads
 */
readonly class BatchUpload
{
    public function __construct(
        public string $id,
        public string $uploadUrl,
        public string $httpMethod = 'PUT',
        public bool $useCdn = true,
        public ?array $multipartFormUploadParams = null,
        public ?string $batchId = null
    ) {
    }

    /**
     * Create BatchUpload from API response data
     */
    public static function fromArray(array $data): self
    {
        $attributes = $data['attributes'] ?? [];
        
        // Extract batch ID from relationships if present
        $batchId = null;
        if (isset($data['relationships']['batch']['data']['id'])) {
            $batchId = $data['relationships']['batch']['data']['id'];
        }
        
        return new self(
            id: $data['id'],
            uploadUrl: $attributes['upload_url'],
            httpMethod: $attributes['http_method'] ?? 'PUT',
            useCdn: $attributes['use_cdn'] ?? true,
            multipartFormUploadParams: $attributes['multipart_form_upload_params'] ?? null,
            batchId: $batchId
        );
    }

    /**
     * Convert to array format for API requests
     */
    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'type' => 'batch-upload',
            'attributes' => [
                'upload_url' => $this->uploadUrl,
                'http_method' => $this->httpMethod,
                'use_cdn' => $this->useCdn
            ]
        ];
        
        if ($this->multipartFormUploadParams !== null) {
            $data['attributes']['multipart_form_upload_params'] = $this->multipartFormUploadParams;
        }
        
        if ($this->batchId !== null) {
            $data['relationships'] = [
                'batch' => [
                    'data' => [
                        'id' => $this->batchId,
                        'type' => 'batch'
                    ]
                ]
            ];
        }
        
        return $data;
    }

    /**
     * Check if this upload uses POST with multipart form data
     */
    public function isMultipartFormUpload(): bool
    {
        return $this->httpMethod === 'POST' && $this->multipartFormUploadParams !== null;
    }

    /**
     * Check if this upload uses simple PUT method
     */
    public function isPutUpload(): bool
    {
        return $this->httpMethod === 'PUT';
    }
}