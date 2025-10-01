<?php

declare(strict_types=1);

namespace DvidsApi\Resource;

use DvidsApi\DvidsApiClient;
use DvidsApi\Model\Publication;
use DvidsApi\Model\PublicationIssue;

/**
 * Client for managing publication and publication issue resources
 */
readonly class PublicationClient
{
    public function __construct(
        private DvidsApiClient $client
    ) {
    }

    /**
     * Get a list of publications with paging and filtering
     *
     * @param array $filters Filters for the search (title)
     * @param int $page Page number of results
     * @param int $limit Limit of results per page
     * @return array{data: Publication[], links: array} Paginated list of publications
     */
    public function getPublications(array $filters = [], int $page = 1, int $limit = 50): array
    {
        $queryParams = array_merge($filters, [
            'page' => $page,
            'limit' => $limit
        ]);

        $response = $this->client->get('/publication', $queryParams);

        $publications = [];
        if (isset($response['data']) && is_array($response['data'])) {
            foreach ($response['data'] as $publicationData) {
                $publications[] = Publication::fromArray($publicationData);
            }
        }

        return [
            'data' => $publications,
            'links' => $response['links'] ?? []
        ];
    }

    /**
     * Search publications by title
     */
    public function searchByTitle(string $title, int $page = 1, int $limit = 50): array
    {
        return $this->getPublications(['title' => $title], $page, $limit);
    }

    /**
     * Create a new publication issue in a batch
     *
     * @param string $batchId Batch ID to create the publication issue in
     * @param array $publicationIssueData Publication issue data following the API schema
     * @return PublicationIssue Created publication issue object
     */
    public function createBatchPublicationIssue(string $batchId, array $publicationIssueData): PublicationIssue
    {
        $response = $this->client->post("/batch/{$batchId}/publication-issue", ['data' => $publicationIssueData]);
        
        if (!isset($response['data'])) {
            throw new \InvalidArgumentException('Invalid publication issue response format');
        }

        return PublicationIssue::fromArray($response['data']);
    }

    /**
     * Get a publication issue by ID from a batch
     *
     * @param string $batchId Batch ID
     * @param string $publicationIssueId Publication issue ID
     * @return PublicationIssue Publication issue object
     */
    public function getBatchPublicationIssue(string $batchId, string $publicationIssueId): PublicationIssue
    {
        $response = $this->client->get("/batch/{$batchId}/publication-issue/{$publicationIssueId}");
        
        if (!isset($response['data'])) {
            throw new \InvalidArgumentException('Invalid publication issue response format');
        }

        return PublicationIssue::fromArray($response['data']);
    }

    /**
     * Update a publication issue in a batch
     *
     * @param string $batchId Batch ID
     * @param string $publicationIssueId Publication issue ID
     * @param array $publicationIssueData Updated publication issue data
     * @return PublicationIssue Updated publication issue object
     */
    public function updateBatchPublicationIssue(string $batchId, string $publicationIssueId, array $publicationIssueData): PublicationIssue
    {
        $response = $this->client->put("/batch/{$batchId}/publication-issue/{$publicationIssueId}", ['data' => $publicationIssueData]);
        
        if (!isset($response['data'])) {
            throw new \InvalidArgumentException('Invalid publication issue response format');
        }

        return PublicationIssue::fromArray($response['data']);
    }

    /**
     * Delete a publication issue from a batch
     *
     * @param string $batchId Batch ID
     * @param string $publicationIssueId Publication issue ID
     * @return bool True if deleted successfully
     */
    public function deleteBatchPublicationIssue(string $batchId, string $publicationIssueId): bool
    {
        $this->client->delete("/batch/{$batchId}/publication-issue/{$publicationIssueId}");
        return true;
    }

    /**
     * Create a publication issue with simplified parameters
     *
     * @param string $batchId Batch ID
     * @param string $description Description of the publication issue
     * @param \DateTimeInterface $createdAt When the publication issue was created
     * @param string $publicationId ID of the publication this issue belongs to
     * @param string $batchUploadId ID of the completed batch upload (PDF file)
     * @return PublicationIssue Created publication issue object
     */
    public function createSimplePublicationIssue(
        string $batchId,
        string $description,
        \DateTimeInterface $createdAt,
        string $publicationId,
        string $batchUploadId
    ): PublicationIssue {
        $publicationIssueData = [
            'id' => '', // Will be generated by API
            'type' => 'publication-issue',
            'attributes' => [
                'description' => $description,
                'created_at' => $createdAt->format('c')
            ],
            'relationships' => [
                'publication' => [
                    'data' => [
                        'id' => $publicationId,
                        'type' => 'publication'
                    ]
                ],
                'batch_upload' => [
                    'data' => [
                        'id' => $batchUploadId,
                        'type' => 'batch-upload'
                    ]
                ]
            ]
        ];

        return $this->createBatchPublicationIssue($batchId, $publicationIssueData);
    }

    /**
     * Complete publication issue workflow: upload PDF and create publication issue
     *
     * @param string $batchId Batch ID
     * @param string $pdfFilePath Path to the PDF file to upload
     * @param string $description Description of the publication issue
     * @param \DateTimeInterface $createdAt When the publication issue was created
     * @param string $publicationId ID of the publication this issue belongs to
     * @param BatchClient $batchClient BatchClient instance for handling uploads
     * @return array{publication_issue: PublicationIssue, upload_result: array} Result containing both the publication issue and upload details
     */
    public function createPublicationIssueWithUpload(
        string $batchId,
        string $pdfFilePath,
        string $description,
        \DateTimeInterface $createdAt,
        string $publicationId,
        BatchClient $batchClient
    ): array {
        // Step 1: Upload the PDF file
        $uploadResult = $batchClient->createAndUploadFile($batchId, $pdfFilePath, 'application/pdf');
        $batchUploadId = $uploadResult['batch_upload']->id;

        // Step 2: Create the publication issue
        $publicationIssue = $this->createSimplePublicationIssue(
            $batchId,
            $description,
            $createdAt,
            $publicationId,
            $batchUploadId
        );

        return [
            'publication_issue' => $publicationIssue,
            'upload_result' => $uploadResult
        ];
    }
}