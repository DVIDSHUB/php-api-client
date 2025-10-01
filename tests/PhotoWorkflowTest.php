<?php

declare(strict_types=1);

namespace DvidsApi\Tests;

use DvidsApi\DvidsClient;
use DvidsApi\Model\Photo;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

/**
 * Advanced PHPUnit test for complete photo workflows with mocked responses
 * 
 * This demonstrates testing entire workflows with multiple API calls
 */
class PhotoWorkflowTest extends TestCase
{
    private array $requestHistory = [];

    protected function setUp(): void
    {
        $this->requestHistory = [];
    }

    private function createMockedClient(array $responses): DvidsClient
    {
        $mockHandler = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mockHandler);
        $handlerStack->push(Middleware::history($this->requestHistory));
        
        $mockGuzzleClient = new Client(['handler' => $handlerStack]);
        
        return DvidsClient::createWithHttpClient($mockGuzzleClient);
    }

    public function testCompletePhotoWorkflowWithServiceUnitVirin(): void
    {
        // Create a temporary file for testing
        $tempFile = tempnam(sys_get_temp_dir(), 'test_photo');
        file_put_contents($tempFile, 'fake image data');
        
        try {
            // Arrange - Mock responses for the entire workflow
            $batchResponse = new Response(201, [], json_encode([
                'data' => [
                    'id' => 'batch-123',
                    'type' => 'batch',
                    'attributes' => ['closed' => false]
                ]
            ]));

            $uploadResponse = new Response(201, [], json_encode([
                'data' => [
                    'id' => 'upload-456',
                    'type' => 'batch-upload',
                    'attributes' => [
                        'upload_url' => 'https://s3.example.com/upload-url',
                        'http_method' => 'PUT',
                        'use_cdn' => true
                    ]
                ]
            ]));

            $fileUploadResponse = new Response(204, []);

            $virinResponse = new Response(201, [], json_encode([
                'data' => [
                    'id' => 'virin-789',
                    'type' => 'service-unit-virin',
                    'attributes' => [
                        'virin' => '241001-A-AB123-001'
                    ]
                ]
            ]));

            $photoResponse = new Response(201, [], json_encode([
                'data' => [
                    'id' => 'photo-999',
                    'type' => 'photo',
                    'attributes' => [
                        'title' => 'Military Training Exercise',
                        'description' => 'Soldiers conducting training exercise',
                        'instructions' => 'Training photo',
                        'created_at' => '2024-10-01T14:30:00Z',
                        'virin' => '241001-A-AB123-001',
                        'country' => 'US'
                    ],
                    'relationships' => [
                        'batch' => ['data' => ['id' => 'batch-123', 'type' => 'batch']],
                        'batch_upload' => ['data' => ['id' => 'upload-456', 'type' => 'batch-upload']]
                    ]
                ]
            ]));

            $closeBatchResponse = new Response(200, [], json_encode([
                'data' => [
                    'id' => 'batch-123',
                    'type' => 'batch',
                    'attributes' => ['closed' => true]
                ]
            ]));

            $client = $this->createMockedClient([
                $batchResponse,
                $uploadResponse,
                $fileUploadResponse,
                $virinResponse,
                $photoResponse,
                $closeBatchResponse
            ]);

            // Act - Execute complete workflow
            $photo = $client->photos()->createCompletePhotoWorkflowWithServiceUnitVirin(
                $tempFile,
                'unit-123',
                new \DateTime('2024-10-01'),
                'Military Training Exercise',
                'Soldiers conducting training exercise',
                'Training photo',
                ['keyword1', 'keyword2'],
                ['author-456', 'author-789'],
                'US' // countryCode
            );

            // Assert
            $this->assertInstanceOf(Photo::class, $photo);
            $this->assertEquals('photo-999', $photo->id);
            $this->assertEquals('Military Training Exercise', $photo->title);
            $this->assertEquals('241001-A-AB123-001', $photo->virin);

            // Verify all workflow steps were executed
            $this->assertCount(6, $this->requestHistory);
        
            // Verify workflow sequence
            $this->assertEquals('POST', $this->requestHistory[0]['request']->getMethod());
            $this->assertEquals('/batch', $this->requestHistory[0]['request']->getUri()->getPath());
            
            $this->assertEquals('POST', $this->requestHistory[1]['request']->getMethod());
            $this->assertEquals('/batch/batch-123/upload', $this->requestHistory[1]['request']->getUri()->getPath());
            
            $this->assertEquals('PUT', $this->requestHistory[2]['request']->getMethod());
            $this->assertEquals('s3.example.com', $this->requestHistory[2]['request']->getUri()->getHost());
            
            $this->assertEquals('POST', $this->requestHistory[3]['request']->getMethod());
            $this->assertEquals('/service-unit/unit-123/virin', $this->requestHistory[3]['request']->getUri()->getPath());
            
            $this->assertEquals('POST', $this->requestHistory[4]['request']->getMethod());
            $this->assertEquals('/batch/batch-123/photo', $this->requestHistory[4]['request']->getUri()->getPath());
            
            $this->assertEquals('PATCH', $this->requestHistory[5]['request']->getMethod());
            $this->assertEquals('/batch/batch-123', $this->requestHistory[5]['request']->getUri()->getPath());
        } finally {
            // Clean up the temporary file
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    public function testCompletePhotoWorkflowWithAuthorVirin(): void
    {
        // Create a temporary file for testing
        $tempFile = tempnam(sys_get_temp_dir(), 'test_photo');
        file_put_contents($tempFile, 'fake image data');
        
        try {
            // Arrange - Mock responses for author VIRIN workflow
        $batchResponse = new Response(201, [], json_encode([
            'data' => [
                'id' => 'batch-456',
                'type' => 'batch',
                'attributes' => ['closed' => false]
            ]
        ]));

        $uploadResponse = new Response(201, [], json_encode([
            'data' => [
                'id' => 'upload-789',
                'type' => 'batch-upload',
                'attributes' => [
                    'upload_url' => 'https://s3.example.com/upload-url-2',
                    'http_method' => 'PUT',
                    'use_cdn' => true
                ]
            ]
        ]));

        $fileUploadResponse = new Response(204, []);

        $virinResponse = new Response(201, [], json_encode([
            'data' => [
                'id' => 'virin-101',
                'type' => 'author-virin',
                'attributes' => [
                    'virin' => '241001-P-JD123-001'
                ]
            ]
        ]));

        $photoResponse = new Response(201, [], json_encode([
            'data' => [
                'id' => 'photo-888',
                'type' => 'photo',
                'attributes' => [
                    'title' => 'Portrait Photo',
                    'description' => 'Professional military portrait',
                    'instructions' => 'Portrait',
                    'created_at' => '2024-10-01T14:30:00Z',
                    'virin' => '241001-P-JD123-001',
                    'country' => 'US'
                ]
            ]
        ]));

        $client = $this->createMockedClient([
            $batchResponse,
            $uploadResponse,
            $fileUploadResponse,
            $virinResponse,
            $photoResponse
        ]);

            // Act - Execute author VIRIN workflow
            $photo = $client->photos()->createCompletePhotoWorkflowWithAuthorVirin(
                $tempFile,
                'author-123',
                new \DateTime('2024-10-01'),
                'Portrait Photo',
                'Professional military portrait',
                'Portrait',
                'unit-456', // serviceUnitId (required)
                ['portrait', 'military'],
                ['author-123'],
                'US' // countryCode
            );

        // Assert
        $this->assertInstanceOf(Photo::class, $photo);
        $this->assertEquals('photo-888', $photo->id);
        $this->assertEquals('Portrait Photo', $photo->title);
        $this->assertEquals('241001-P-JD123-001', $photo->virin);

            // Verify author VIRIN generation request
            $virinRequest = $this->requestHistory[3]['request'];
            $this->assertEquals('POST', $virinRequest->getMethod());
            $this->assertEquals('/author/author-123/virin', $virinRequest->getUri()->getPath());
            
            $virinRequestBody = json_decode((string) $virinRequest->getBody(), true);
            $this->assertEquals('author-virin', $virinRequestBody['data']['type']);
        } finally {
            // Clean up the temporary file
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    public function testCreatePhotoWithAuthorGeneratedVirin(): void
    {
        // Arrange
        $virinResponse = new Response(201, [], json_encode([
            'data' => [
                'id' => 'virin-202',
                'type' => 'author-virin',
                'attributes' => [
                    'virin' => '241002-P-AB123-002'
                ]
            ]
        ]));

        $photoResponse = new Response(201, [], json_encode([
            'data' => [
                'id' => 'photo-777',
                'type' => 'photo',
                'attributes' => [
                    'title' => 'Event Photo',
                    'description' => 'Photo of military event',
                    'instructions' => 'Event photo alt text',
                    'created_at' => '2024-10-02T14:30:00Z',
                    'virin' => '241002-P-AB123-002',
                    'country' => 'US'
                ]
            ]
        ]));

        $client = $this->createMockedClient([$virinResponse, $photoResponse]);

        // Act
        $photo = $client->photos()->createPhotoWithAuthorGeneratedVirin(
            'batch-id',                        // batchId
            'Event Photo',                     // title 
            'Photo of military event',         // description
            'Event photo alt text',            // instructions
            new \DateTime('2024-10-02'),       // createdAt
            'US',                              // country
            'upload-id',                       // batchUploadId
            'author-456',                      // authorId
            ['event', 'ceremony'],             // tags
            null,                              // subdiv (optional)
            'event-location',                  // city (optional)
            null,                              // captionWriter (optional)
            null,                              // jobIdentifier (optional)
            null,                              // operationName (optional)
            'service-unit-789',                // serviceUnitId (required for createSimplePhoto)
            ['author-456', 'author-789']       // authorIds
        );

        // Assert
        $this->assertInstanceOf(Photo::class, $photo);
        $this->assertEquals('photo-777', $photo->id);
        $this->assertEquals('Event Photo', $photo->title);
        $this->assertEquals('241002-P-AB123-002', $photo->virin);

        // Verify VIRIN generation and photo creation requests
        $this->assertCount(2, $this->requestHistory);
        
        // Check VIRIN generation
        $virinRequest = $this->requestHistory[0]['request'];
        $this->assertEquals('POST', $virinRequest->getMethod());
        $this->assertEquals('/author/author-456/virin', $virinRequest->getUri()->getPath());
        
        // Check photo creation
        $photoRequest = $this->requestHistory[1]['request'];
        $this->assertEquals('POST', $photoRequest->getMethod());
        $this->assertEquals('/batch/batch-id/photo', $photoRequest->getUri()->getPath());
        
        $photoRequestBody = json_decode((string) $photoRequest->getBody(), true);
        $this->assertEquals('241002-P-AB123-002', $photoRequestBody['data']['attributes']['virin']);
        
        // Verify author is included in credited authors
        $creditedAuthors = $photoRequestBody['data']['relationships']['authors']['data'];
        $authorIds = array_column($creditedAuthors, 'id');
        $this->assertContains('author-456', $authorIds);
        $this->assertContains('author-789', $authorIds);
    }

    public function testFileUploadWithMockedS3(): void
    {
        // Create a temporary file for testing
        $tempFile = tempnam(sys_get_temp_dir(), 'test_upload');
        file_put_contents($tempFile, 'fake image data');
        
        try {
            // Arrange
            $uploadResponse = new Response(201, [], json_encode([
                'data' => [
                    'id' => 'upload-123',
                    'type' => 'batch-upload',
                    'attributes' => [
                        'upload_url' => 'https://s3.amazonaws.com/dvids-uploads/test',
                        'http_method' => 'PUT',
                        'use_cdn' => false
                    ]
                ]
            ]));

            $s3UploadResponse = new Response(204, []);

            $client = $this->createMockedClient([$uploadResponse, $s3UploadResponse]);

            // Act
            $upload = $client->batches()->createBatchUpload('batch-123');
            $result = $client->batches()->uploadFile($upload, $tempFile, 'image/jpeg');

            // Assert
            $this->assertIsArray($result);
            $this->assertArrayHasKey('batch_upload', $result);
            $this->assertArrayHasKey('upload_result', $result);
            $this->assertInstanceOf(\DvidsApi\Model\BatchUpload::class, $result['batch_upload']);
            
            // Verify S3 upload request
            $this->assertCount(2, $this->requestHistory);
            
            $s3Request = $this->requestHistory[1]['request'];
            $this->assertEquals('PUT', $s3Request->getMethod());
            $this->assertEquals('s3.amazonaws.com', $s3Request->getUri()->getHost());
        } finally {
            // Clean up the temporary file
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    public function testErrorHandlingInWorkflow(): void
    {
        // Create a temporary file for testing
        $tempFile = tempnam(sys_get_temp_dir(), 'test_photo');
        file_put_contents($tempFile, 'fake image data');
        
        // Arrange - Mock a workflow where VIRIN generation fails
        $batchResponse = new Response(201, [], json_encode([
            'data' => ['id' => 'batch-123', 'type' => 'batch', 'attributes' => ['closed' => false]]
        ]));

        $uploadResponse = new Response(201, [], json_encode([
            'data' => ['id' => 'upload-456', 'type' => 'batch-upload', 'attributes' => ['upload_url' => 'https://s3.example.com/upload', 'http_method' => 'PUT', 'use_cdn' => true]]
        ]));

        $fileUploadResponse = new Response(204, []);

        $virinErrorResponse = new Response(400, [], json_encode([
            'errors' => [
                [
                    'title' => 'Bad Request',
                    'detail' => 'Invalid service unit ID',
                    'status' => '400'
                ]
            ]
        ]));

        $client = $this->createMockedClient([
            $batchResponse,
            $uploadResponse,
            $fileUploadResponse,
            $virinErrorResponse
        ]);

        try {
            // Act & Assert
            $this->expectException(\DvidsApi\Exception\BadRequestException::class);
            $this->expectExceptionMessage('Bad Request: Invalid service unit ID');

            $client->photos()->createCompletePhotoWorkflowWithServiceUnitVirin(
                $tempFile,  // Use the temporary file instead of fake path
                'invalid-unit-id',
                new \DateTime('2024-10-01'),
                'Test Photo',
                'Test description',
                'Test alt text',
                ['test'],
                ['author-123'],
                'US' // countryCode
            );

            // Verify that workflow stopped at VIRIN generation
            $this->assertCount(4, $this->requestHistory); // batch, upload, file upload, virin (failed)
        } finally {
            // Clean up the temporary file
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }
}