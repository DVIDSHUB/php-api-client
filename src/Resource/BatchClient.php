<?php

declare(strict_types=1);

namespace DvidsApi\Resource;

use DvidsApi\DvidsApiClient;
use DvidsApi\Model\Batch;
use DvidsApi\Model\BatchUpload;

/**
 * Client for managing batch resources and file uploads
 */
readonly class BatchClient
{
    public function __construct(
        private DvidsApiClient $client
    ) {
    }

    /**
     * Create a new batch for file submissions
     */
    public function createBatch(): Batch
    {
        $data = [
            'data' => [
                'type' => 'batch',
                'attributes' => []
            ]
        ];

        $response = $this->client->post('/batch', $data);
        
        if (!isset($response['data'])) {
            throw new \InvalidArgumentException('Invalid batch response format');
        }

        return Batch::fromArray($response['data']);
    }

    /**
     * Get a batch by ID
     */
    public function getBatch(string $id): Batch
    {
        $response = $this->client->get("/batch/{$id}");
        
        if (!isset($response['data'])) {
            throw new \InvalidArgumentException('Invalid batch response format');
        }

        return Batch::fromArray($response['data']);
    }

    /**
     * Close a batch (submit for approval/publishing)
     */
    public function closeBatch(string $id, bool $sendConfirmationEmail = true): Batch
    {
        $data = [
            'data' => [
                'id' => $id,
                'type' => 'batch',
                'attributes' => [
                    'closed' => true,
                    'send_confirmation_email' => $sendConfirmationEmail
                ]
            ]
        ];

        $response = $this->client->patch("/batch/{$id}", $data);
        
        if (!isset($response['data'])) {
            throw new \InvalidArgumentException('Invalid batch response format');
        }

        return Batch::fromArray($response['data']);
    }

    /**
     * Create a batch upload for files up to 5GB
     */
    public function createBatchUpload(string $batchId, bool $useCdn = true): BatchUpload
    {
        $data = [
            'data' => [
                'type' => 'batch-upload',
                'attributes' => [
                    'use_cdn' => $useCdn
                ],
                'relationships' => [
                    'batch' => [
                        'data' => [
                            'id' => $batchId,
                            'type' => 'batch'
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->client->post("/batch/{$batchId}/upload", $data);
        
        if (!isset($response['data'])) {
            throw new \InvalidArgumentException('Invalid batch upload response format');
        }

        return BatchUpload::fromArray($response['data']);
    }

    /**
     * Create a multipart upload for files between 5GB and 5TB
     */
    public function createBatchMultipartUpload(string $batchId, string $contentType): array
    {
        $data = [
            'data' => [
                'type' => 'batch-multipart-upload',
                'attributes' => [
                    'content_type' => $contentType
                ],
                'relationships' => [
                    'batch' => [
                        'data' => [
                            'id' => $batchId,
                            'type' => 'batch'
                        ]
                    ]
                ]
            ]
        ];

        return $this->client->post("/batch/{$batchId}/multipart-upload", $data);
    }

    /**
     * Create a multipart upload part
     */
    public function createBatchMultipartUploadPart(
        string $batchId, 
        string $multipartUploadId, 
        int $partNumber
    ): array {
        $data = [
            'data' => [
                'type' => 'batch-multipart-upload-part',
                'attributes' => [
                    'part_number' => $partNumber
                ]
            ]
        ];

        return $this->client->post(
            "/batch/{$batchId}/multipart-upload/{$multipartUploadId}/part",
            $data
        );
    }

    /**
     * Complete a multipart upload
     */
    public function completeBatchMultipartUpload(
        string $batchId,
        string $multipartUploadId,
        array $parts
    ): array {
        $data = [
            'data' => [
                'id' => $multipartUploadId,
                'type' => 'batch-multipart-upload',
                'attributes' => [
                    'parts' => $parts
                ]
            ]
        ];

        return $this->client->patch(
            "/batch/{$batchId}/multipart-upload/{$multipartUploadId}",
            $data
        );
    }

    /**
     * Upload a file using a BatchUpload object with presigned URL
     *
     * @param BatchUpload $batchUpload The batch upload object containing upload URL and parameters
     * @param string $filePath Path to the file to upload
     * @param string $contentType MIME type of the file
     * @return array Upload response containing batch_upload and upload_result
     */
    public function uploadFile(
        BatchUpload $batchUpload,
        string $filePath,
        string $contentType
    ): array {
        if ($batchUpload->isMultipartFormUpload()) {
            return $this->uploadFileWithMultipartForm($batchUpload, $filePath, $contentType);
        }
        
        if ($batchUpload->isPutUpload()) {
            return $this->uploadFileWithPut($batchUpload, $filePath, $contentType);
        }
        
        throw new \RuntimeException('Unsupported HTTP method: ' . $batchUpload->httpMethod);
    }

    /**
     * Create a batch upload and upload a file in one operation
     *
     * @param string $batchId The batch ID
     * @param string $filePath Path to the file to upload
     * @param string $contentType MIME type of the file
     * @param bool $useCdn Whether to use CDN acceleration
     * @return array Upload response containing batch_upload and upload_result
     */
    public function createAndUploadFile(
        string $batchId,
        string $filePath,
        string $contentType,
        bool $useCdn = true
    ): array {
        // First create the batch upload
        $batchUpload = $this->createBatchUpload($batchId, $useCdn);
        
        // Then upload the file
        return $this->uploadFile($batchUpload, $filePath, $contentType);
    }

    /**
     * Upload file using PUT method (simple upload)
     */
    private function uploadFileWithPut(
        BatchUpload $batchUpload,
        string $filePath,
        string $contentType
    ): array {
        $uploadResult = $this->client->uploadFile(
            $batchUpload->uploadUrl, 
            $filePath, 
            $contentType
        );
        
        return [
            'batch_upload' => $batchUpload,
            'upload_result' => $uploadResult
        ];
    }

    /**
     * Upload file using POST with multipart form data
     */
    private function uploadFileWithMultipartForm(
        BatchUpload $batchUpload,
        string $filePath,
        string $contentType
    ): array {
        if (!$batchUpload->multipartFormUploadParams) {
            throw new \RuntimeException('No multipart form parameters provided for POST upload');
        }

        // For now, throw an exception as multipart form uploads need more complex implementation
        throw new \RuntimeException('POST uploads with multipart form data not yet implemented. Please use PUT uploads.');
        
        // TODO: Implement multipart form data upload
        // This would require creating multipart/form-data body with the file
        // and all the form parameters from multipartFormUploadParams
    }
}