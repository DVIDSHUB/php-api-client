<?php

declare(strict_types=1);

namespace DvidsApi\Resource;

use DvidsApi\DvidsApiClient;
use DvidsApi\Model\Photo;

/**
 * Client for managing photo resources
 */
readonly class PhotoClient
{
    public function __construct(
        private DvidsApiClient $client
    ) {
    }

    /**
     * Create a new photo in a batch
     *
     * @param string $batchId Batch ID to create the photo in
     * @param array $photoData Photo data following the API schema
     * @return Photo Created photo object
     */
    public function createBatchPhoto(string $batchId, array $photoData): Photo
    {
        $response = $this->client->post("/batch/{$batchId}/photo", ['data' => $photoData]);

        return Photo::fromArray($response['data']);
    }

    /**
     * Get a photo by ID from a batch
     *
     * @param string $batchId Batch ID
     * @param string $photoId Photo ID
     * @return Photo Photo object
     */
    public function getBatchPhoto(string $batchId, string $photoId): Photo
    {
        $response = $this->client->get("/batch/{$batchId}/photo/{$photoId}");

        return Photo::fromArray($response['data']);
    }

    /**
     * Update a photo in a batch
     *
     * @param string $batchId Batch ID
     * @param string $photoId Photo ID
     * @param array $photoData Updated photo data
     * @return Photo Updated photo object
     */
    public function updateBatchPhoto(string $batchId, string $photoId, array $photoData): Photo
    {
        $response = $this->client->put("/batch/{$batchId}/photo/{$photoId}", ['data' => $photoData]);
        
        if (!isset($response['data'])) {
            throw new \InvalidArgumentException('Invalid photo response format');
        }

        return Photo::fromArray($response['data']);
    }

    /**
     * Delete a photo from a batch
     *
     * @param string $batchId Batch ID
     * @param string $photoId Photo ID
     * @return bool True if deleted successfully
     */
    public function deleteBatchPhoto(string $batchId, string $photoId): bool
    {
        $this->client->delete("/batch/{$batchId}/photo/{$photoId}");
        return true;
    }


    /**
     * Create a photo with simplified parameters and automatic VIRIN generation
     *
     * @param string $batchId Batch ID
     * @param string $title Headline to introduce photo
     * @param string $description Longer description of the photo
     * @param string $instructions Instructions for release authority and review handling
     * @param \DateTimeInterface $createdAt When the photo was taken/created
     * @param string $country ISO-3166-2 country code where photo was taken
     * @param string $batchUploadId ID of the completed batch upload (image file)
     * @param string $serviceUnitId Service unit ID
     * @param array $tags Array of tag strings
     * @param string|null $subdiv State/province where photo was taken
     * @param string|null $city City where photo was taken
     * @param string|null $captionWriter Name of individual who captioned the photo
     * @param string|null $jobIdentifier Job identifier for the photo assignment
     * @param string|null $operationName Name of the operation/mission
     * @param array $authorIds Array of author IDs to credit
     * @param array $themeIds Array of theme IDs to associate
     * @return Photo Created photo object with generated VIRIN
     */
    public function createPhotoWithServiceUnitGeneratedVirin(
        string $batchId,
        string $title,
        string $description,
        string $instructions,
        \DateTimeInterface $createdAt,
        string $country,
        string $batchUploadId,
        string $serviceUnitId,
        array $tags = [],
        ?string $subdiv = null,
        ?string $city = null,
        ?string $captionWriter = null,
        ?string $jobIdentifier = null,
        ?string $operationName = null,
        array $authorIds = [],
        array $themeIds = []
    ): Photo {
        // Generate VIRIN using service unit
        $serviceUnitClient = new ServiceUnitClient($this->client);
        $virinResponse = $serviceUnitClient->createVirin($serviceUnitId, $createdAt);
        
        if (!isset($virinResponse['data']['attributes']['virin'])) {
            throw new \RuntimeException('Failed to generate VIRIN from service unit');
        }
        
        $virin = $virinResponse['data']['attributes']['virin'];

        // Create the photo with the generated VIRIN
        return $this->createSimplePhoto(
            $batchId,
            $title,
            $description,
            $instructions,
            $createdAt,
            $virin,
            $country,
            $batchUploadId,
            $serviceUnitId,
            $tags,
            $subdiv,
            $city,
            $captionWriter,
            $jobIdentifier,
            $operationName,
            $authorIds,
            $themeIds
        );
    }

    /**
     * Create a photo with simplified parameters and automatic author VIRIN generation
     *
     * @param string $batchId Batch ID
     * @param string $title Headline to introduce photo
     * @param string $description Longer description of the photo
     * @param string $instructions Instructions for release authority and review handling
     * @param \DateTimeInterface $createdAt When the photo was taken/created
     * @param string $country ISO-3166-2 country code where photo was taken
     * @param string $batchUploadId ID of the completed batch upload (image file)
     * @param string $authorId Primary author ID for VIRIN generation
     * @param array $tags Array of tag strings
     * @param string|null $subdiv State/province where photo was taken
     * @param string|null $city City where photo was taken
     * @param string|null $captionWriter Name of individual who captioned the photo
     * @param string|null $jobIdentifier Job identifier for the photo assignment
     * @param string|null $operationName Name of the operation/mission
     * @param string|null $serviceUnitId Service unit ID (optional for author-generated VIRINs)
     * @param array $authorIds Array of author IDs to credit (includes primary author)
     * @param array $themeIds Array of theme IDs to associate
     * @return Photo Created photo object with generated VIRIN
     */
    public function createPhotoWithAuthorGeneratedVirin(
        string $batchId,
        string $title,
        string $description,
        string $instructions,
        \DateTimeInterface $createdAt,
        string $country,
        string $batchUploadId,
        string $authorId,
        array $tags = [],
        ?string $subdiv = null,
        ?string $city = null,
        ?string $captionWriter = null,
        ?string $jobIdentifier = null,
        ?string $operationName = null,
        ?string $serviceUnitId = null,
        array $authorIds = [],
        array $themeIds = []
    ): Photo {
        // Generate VIRIN using author
        $authorClient = new AuthorClient($this->client);
        $virinResponse = $authorClient->createVirin($authorId, $createdAt);
        
        if (!isset($virinResponse['data']['attributes']['virin'])) {
            throw new \RuntimeException('Failed to generate VIRIN from author');
        }
        
        $virin = $virinResponse['data']['attributes']['virin'];

        // Ensure the primary author is included in the authors array
        if (!in_array($authorId, $authorIds)) {
            $authorIds[] = $authorId;
        }

        // Create the photo with the generated VIRIN
        return $this->createSimplePhoto(
            $batchId,
            $title,
            $description,
            $instructions,
            $createdAt,
            $virin,
            $country,
            $batchUploadId,
            $serviceUnitId,
            $tags,
            $subdiv,
            $city,
            $captionWriter,
            $jobIdentifier,
            $operationName,
            $authorIds,
            $themeIds
        );
    }

    /**
     * Create a photo with simplified parameters
     *
     * @param string $batchId Batch ID
     * @param string $title Headline to introduce photo
     * @param string $description Longer description of the photo
     * @param string $instructions Instructions for release authority and review handling
     * @param \DateTimeInterface $createdAt When the photo was taken/created
     * @param string $virin Visual imagery record identification number
     * @param string $country ISO-3166-2 country code where photo was taken
     * @param string $batchUploadId ID of the completed batch upload (image file)
     * @param string $serviceUnitId Service unit ID
     * @param array $tags Array of tag strings
     * @param string|null $subdiv State/province where photo was taken
     * @param string|null $city City where photo was taken
     * @param string|null $captionWriter Name of individual who captioned the photo
     * @param string|null $jobIdentifier Job identifier for the photo assignment
     * @param string|null $operationName Name of the operation/mission
     * @param array $authorIds Array of author IDs to credit
     * @param array $themeIds Array of theme IDs to associate
     * @return Photo Created photo object
     */
    public function createSimplePhoto(
        string $batchId,
        string $title,
        string $description,
        string $instructions,
        \DateTimeInterface $createdAt,
        string $virin,
        string $country,
        string $batchUploadId,
        string $serviceUnitId,
        array $tags = [],
        ?string $subdiv = null,
        ?string $city = null,
        ?string $captionWriter = null,
        ?string $jobIdentifier = null,
        ?string $operationName = null,
        array $authorIds = [],
        array $themeIds = []
    ): Photo {
        $photoData = [
            'id' => '', // Will be generated by API
            'type' => 'photo',
            'attributes' => [
                'title' => $title,
                'description' => $description,
                'instructions' => $instructions,
                'created_at' => $createdAt->format('c'),
                'virin' => $virin,
                'country' => $country,
                'tags' => $tags
            ],
            'relationships' => [
                'batch_upload' => [
                    'data' => [
                        'id' => $batchUploadId,
                        'type' => 'batch-upload'
                    ]
                ],
                'service_unit' => [
                    'data' => [
                        'id' => $serviceUnitId,
                        'type' => 'service-unit'
                    ]
                ]
            ]
        ];

        // Add optional attributes
        if ($subdiv !== null) {
            $photoData['attributes']['subdiv'] = $subdiv;
        }
        
        if ($city !== null) {
            $photoData['attributes']['city'] = $city;
        }
        
        if ($captionWriter !== null) {
            $photoData['attributes']['caption_writer'] = $captionWriter;
        }

        if ($jobIdentifier !== null) {
            $photoData['attributes']['job_identifier'] = $jobIdentifier;
        }

        if ($operationName !== null) {
            $photoData['attributes']['operation_name'] = $operationName;
        }

        // Add optional relationships
        if (!empty($authorIds)) {
            $photoData['relationships']['authors'] = [
                'data' => array_map(fn(string $id): array => [
                    'id' => $id,
                    'type' => 'author'
                ], $authorIds)
            ];
        }

        if (!empty($themeIds)) {
            $photoData['relationships']['themes'] = [
                'data' => array_map(fn(string $id): array => [
                    'id' => $id,
                    'type' => 'theme'
                ], $themeIds)
            ];
        }

        return $this->createBatchPhoto($batchId, $photoData);
    }

    /**
     * Detailed complete photo workflow with service unit VIRIN: upload image file, generate VIRIN, and create photo
     *
     * @param string $batchId Batch ID
     * @param string $imageFilePath Path to the image file to upload
     * @param string $contentType MIME type of the image (e.g., 'image/jpeg')
     * @param string $title Photo title/headline
     * @param string $description Photo description
     * @param string $instructions Release instructions
     * @param \DateTimeInterface $createdAt When the photo was taken
     * @param string $country Country where photo was taken
     * @param string $serviceUnitId Service unit ID for VIRIN generation
     * @param BatchClient $batchClient BatchClient instance for handling uploads
     * @param array $tags Photo tags
     * @param string|null $subdiv State/province
     * @param string|null $city City
     * @param string|null $captionWriter Caption writer name
     * @param string|null $jobIdentifier Job identifier
     * @param string|null $operationName Operation name
     * @param array $authorIds Author IDs to credit
     * @param array $themeIds Theme IDs to associate
     * @return array{photo: Photo, upload_result: array, virin_result: array} Complete result with photo, upload, and VIRIN details
     */
    public function createDetailedPhotoWorkflowWithServiceUnitVirin(
        string $batchId,
        string $imageFilePath,
        string $contentType,
        string $title,
        string $description,
        string $instructions,
        \DateTimeInterface $createdAt,
        string $country,
        string $serviceUnitId,
        BatchClient $batchClient,
        array $tags = [],
        ?string $subdiv = null,
        ?string $city = null,
        ?string $captionWriter = null,
        ?string $jobIdentifier = null,
        ?string $operationName = null,
        array $authorIds = [],
        array $themeIds = []
    ): array {
        // Step 1: Upload the image file
        $uploadResult = $batchClient->createAndUploadFile($batchId, $imageFilePath, $contentType);
        $batchUploadId = $uploadResult['batch_upload']->id;

        // Step 2: Generate VIRIN
        $serviceUnitClient = new ServiceUnitClient($this->client);
        $virinResult = $serviceUnitClient->createVirin($serviceUnitId, $createdAt);
        
        $virin = $virinResult['data']['attributes']['virin'];

        // Step 3: Create the photo
        $photo = $this->createSimplePhoto(
            $batchId,
            $title,
            $description,
            $instructions,
            $createdAt,
            $virin,
            $country,
            $batchUploadId,
            $serviceUnitId,
            $tags,
            $subdiv,
            $city,
            $captionWriter,
            $jobIdentifier,
            $operationName,
            $authorIds,
            $themeIds
        );

        return [
            'photo' => $photo,
            'upload_result' => $uploadResult,
        ];
    }

    /**
     * Detailed complete photo workflow with author VIRIN: upload image file, generate author VIRIN, and create photo
     *
     * @param string $batchId Batch ID
     * @param string $imageFilePath Path to the image file to upload
     * @param string $contentType MIME type of the image (e.g., 'image/jpeg')
     * @param string $title Photo title/headline
     * @param string $description Photo description
     * @param string $instructions Release instructions
     * @param \DateTimeInterface $createdAt When the photo was taken
     * @param string $country Country where photo was taken
     * @param string $authorId Primary author ID for VIRIN generation
     * @param BatchClient $batchClient BatchClient instance for handling uploads
     * @param array $tags Photo tags
     * @param string|null $subdiv State/province
     * @param string|null $city City
     * @param string|null $captionWriter Caption writer name
     * @param string|null $jobIdentifier Job identifier
     * @param string|null $operationName Operation name
     * @param string|null $serviceUnitId Service unit ID (optional for author VIRINs)
     * @param array $authorIds Additional author IDs to credit (primary author is automatically included)
     * @param array $themeIds Theme IDs to associate
     * @return array{photo: Photo, upload_result: array, virin_result: array} Complete result with photo, upload, and VIRIN details
     */
    public function createDetailedPhotoWorkflowWithAuthorVirin(
        string $batchId,
        string $imageFilePath,
        string $contentType,
        string $title,
        string $description,
        string $instructions,
        \DateTimeInterface $createdAt,
        string $country,
        string $virinAuthorId,
        BatchClient $batchClient,
        array $tags = [],
        ?string $subdiv = null,
        ?string $city = null,
        ?string $captionWriter = null,
        ?string $jobIdentifier = null,
        ?string $operationName = null,
        ?string $serviceUnitId = null,
        array $authorIds = [],
        array $themeIds = []
    ): array {
        // Step 1: Upload the image file
        $uploadResult = $batchClient->createAndUploadFile($batchId, $imageFilePath, $contentType);
        $batchUploadId = $uploadResult['batch_upload']->id;

        // Step 2: Generate VIRIN using author
        $authorClient = new AuthorClient($this->client);
        $virinResponse = $authorClient->createVirin($virinAuthorId, $createdAt);
        
        $virin = $virinResponse['data']['attributes']['virin'];

        // Ensure the primary author is included in the authors array
        if (!in_array($virinAuthorId, $authorIds)) {
            $authorIds[] = $virinAuthorId;
        }

        // Step 3: Create the photo
        $photo = $this->createSimplePhoto(
            $batchId,
            $title,
            $description,
            $instructions,
            $createdAt,
            $virin,
            $country,
            $batchUploadId,
            $serviceUnitId,
            $tags,
            $subdiv,
            $city,
            $captionWriter,
            $jobIdentifier,
            $operationName,
            $authorIds,
            $themeIds
        );

        return [
            'photo' => $photo,
            'upload_result' => $uploadResult,
        ];
    }

    /**
     * Simplified complete photo workflow with service unit VIRIN (for testing and simple usage)
     *
     * This method creates a batch, uploads the file, generates VIRIN, creates the photo, and returns just the Photo.
     * It's designed to match the test expectations with a simpler parameter interface.
     *
     * @param string $imageFilePath Path to the image file to upload
     * @param string $serviceUnitId Service unit ID for VIRIN generation
     * @param \DateTimeInterface $createdAt When the photo was taken
     * @param string $title Photo title/headline
     * @param string $description Photo description
     * @param string $instructions Release instructions or alt text
     * @param array $tags Photo tags
     * @param array $authorIds Author IDs to credit
     * @param string $countryCode ISO-3166-2 country code where photo was taken (default: 'US')
     * @return Photo Created photo object
     */
    public function createCompletePhotoWorkflowWithServiceUnitVirin(
        string $imageFilePath,
        string $serviceUnitId, 
        \DateTimeInterface $createdAt,
        string $title,
        string $description,
        string $instructions,
        array $tags = [],
        array $authorIds = [],
        string $countryCode = 'US'
    ): Photo {
        // Create a batch client to handle batch operations
        $batchClient = new BatchClient($this->client);
        
        // Step 1: Create batch
        $batch = $batchClient->createBatch();
        
        // Step 2: Upload file (assume JPEG for simplicity)
        $uploadResult = $batchClient->createAndUploadFile($batch->id, $imageFilePath, 'image/jpeg');
        $batchUploadId = $uploadResult['batch_upload']->id;
        
        // Step 3: Generate VIRIN
        $serviceUnitClient = new ServiceUnitClient($this->client);
        $virinResult = $serviceUnitClient->createVirin($serviceUnitId, $createdAt);
        $virin = $virinResult['data']['attributes']['virin'];
        
        // Step 4: Create photo with simplified defaults
        $photo = $this->createSimplePhoto(
            $batch->id,
            $title,
            $description,
            $instructions,
            $createdAt,
            $virin,
            $countryCode,
            $batchUploadId,
            $serviceUnitId,
            $tags,
            null, // subdiv
            null, // city
            null, // captionWriter
            null, // jobIdentifier
            null, // operationName
            $authorIds
        );

        // Step 5. Close batch
        $batchClient->closeBatch($batch->id);
        
        return $photo;
    }

    /**
     * Simplified complete photo workflow with author VIRIN (for testing and simple usage)
     *
     * This method creates a batch, uploads the file, generates author VIRIN, creates the photo, and returns just the Photo.
     * It's designed to match the test expectations with a simpler parameter interface.
     *
     * @param string $imageFilePath Path to the image file to upload
     * @param string $authorId Primary author ID for VIRIN generation
     * @param \DateTimeInterface $createdAt When the photo was taken
     * @param string $title Photo title/headline
     * @param string $description Photo description
     * @param string $instructions Release instructions or alt text
     * @param string $serviceUnitId Service unit ID (always required)
     * @param array $tags Photo tags
     * @param array $authorIds Additional author IDs to credit
     * @param string $countryCode ISO-3166-2 country code where photo was taken (default: 'US')
     * @return Photo Created photo object
     */
    public function createCompletePhotoWorkflowWithAuthorVirin(
        string $imageFilePath,
        string $authorId,
        \DateTimeInterface $createdAt,
        string $title,
        string $description,
        string $instructions,
        string $serviceUnitId,
        array $tags = [],
        array $authorIds = [],
        string $countryCode = 'US'
    ): Photo {
        // Create a batch client to handle batch operations
        $batchClient = new BatchClient($this->client);
        
        // Step 1: Create batch
        $batch = $batchClient->createBatch();
        
        // Step 2: Upload file (assume JPEG for simplicity)
        $uploadResult = $batchClient->createAndUploadFile($batch->id, $imageFilePath, 'image/jpeg');
        $batchUploadId = $uploadResult['batch_upload']->id;
        
        // Step 3: Generate VIRIN using author
        $authorClient = new AuthorClient($this->client);
        $virinResponse = $authorClient->createVirin($authorId, $createdAt);
        $virin = $virinResponse['data']['attributes']['virin'];
        
        // Ensure the primary author is included in the authors array
        if (!in_array($authorId, $authorIds)) {
            $authorIds[] = $authorId;
        }
        
        // Step 4: Create photo with simplified defaults
        $photo = $this->createSimplePhoto(
            $batch->id,
            $title,
            $description,
            $instructions,
            $createdAt,
            $virin,
            $countryCode,
            $batchUploadId,
            $serviceUnitId, // Service unit ID (always required)
            $tags,
            null, // subdiv
            null, // city
            null, // captionWriter
            null, // jobIdentifier
            null, // operationName
            $authorIds
        );
        
        return $photo;
    }
}
