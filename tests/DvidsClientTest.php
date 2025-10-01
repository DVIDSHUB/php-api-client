<?php

declare(strict_types=1);

namespace DvidsApi\Tests;

use DvidsApi\DvidsClient;
use DvidsApi\Model\Batch;
use DvidsApi\Model\BatchUpload;
use DvidsApi\Exception\NotFoundException;
use DvidsApi\Exception\UnauthorizedException;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;

/**
 * PHPUnit test example for DVIDS Client with Guzzle mocking
 * 
 * This demonstrates proper unit testing patterns with mocked HTTP responses
 */
class DvidsClientTest extends TestCase
{
    private array $requestHistory = [];

    protected function setUp(): void
    {
        $this->requestHistory = [];
    }

    /**
     * Create a mocked DVIDS client with predefined responses
     */
    private function createMockedClient(array $responses): DvidsClient
    {
        $mockHandler = new MockHandler($responses);
        
        // Add history middleware to track requests
        $handlerStack = HandlerStack::create($mockHandler);
        $handlerStack->push(Middleware::history($this->requestHistory));
        
        $mockGuzzleClient = new Client(['handler' => $handlerStack]);
        
        return DvidsClient::createWithHttpClient($mockGuzzleClient);
    }

    public function testCreateBatch(): void
    {
        // Arrange
        $mockResponse = new Response(201, ['Content-Type' => 'application/vnd.api+json'], json_encode([
            'data' => [
                'id' => 'batch-123',
                'type' => 'batch',
                'attributes' => [
                    'closed' => false,
                    'created_at' => '2024-10-01T10:00:00Z'
                ]
            ]
        ]));

        $client = $this->createMockedClient([$mockResponse]);

        // Act
        $batch = $client->batches()->createBatch();

        // Assert
        $this->assertInstanceOf(Batch::class, $batch);
        $this->assertEquals('batch-123', $batch->id);
        $this->assertFalse($batch->closed);
        
        // Verify the request was made correctly
        $this->assertCount(1, $this->requestHistory);
        $request = $this->requestHistory[0]['request'];
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('/batch', $request->getUri()->getPath());
    }

    public function testCreateBatchUpload(): void
    {
        // Arrange
        $mockResponse = new Response(201, ['Content-Type' => 'application/vnd.api+json'], json_encode([
            'data' => [
                'id' => 'upload-456',
                'type' => 'batch-upload',
                'attributes' => [
                    'upload_url' => 'https://s3.example.com/upload-url',
                    'http_method' => 'PUT',
                    'use_cdn' => true
                ],
                'relationships' => [
                    'batch' => [
                        'data' => ['id' => 'batch-123', 'type' => 'batch']
                    ]
                ]
            ]
        ]));

        $client = $this->createMockedClient([$mockResponse]);

        // Act
        $batchUpload = $client->batches()->createBatchUpload('batch-123');

        // Assert
        $this->assertInstanceOf(BatchUpload::class, $batchUpload);
        $this->assertEquals('upload-456', $batchUpload->id);
        $this->assertEquals('https://s3.example.com/upload-url', $batchUpload->uploadUrl);
        $this->assertEquals('PUT', $batchUpload->httpMethod);
        $this->assertTrue($batchUpload->useCdn);
        
        // Verify the request
        $this->assertCount(1, $this->requestHistory);
        $request = $this->requestHistory[0]['request'];
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('/batch/batch-123/upload', $request->getUri()->getPath());
    }

    public function testSearchAuthors(): void
    {
        // Arrange
        $mockResponse = new Response(200, ['Content-Type' => 'application/vnd.api+json'], json_encode([
            'data' => [
                [
                    'id' => 'author-123',
                    'type' => 'author',
                    'attributes' => [
                        'name' => 'John Doe',
                        'vision_id' => 'JD123'
                    ],
                    'relationships' => [
                        'service_units' => ['data' => []],
                        'job_grade' => ['data' => null]
                    ]
                ]
            ],
            'links' => [
                'self' => 'https://submitapi.dvidshub.net/author'
            ]
        ]));

        $client = $this->createMockedClient([$mockResponse]);

        // Act
        $result = $client->authors()->searchByName('John Doe');

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertCount(1, $result['data']);
        
        $author = $result['data'][0];
        $this->assertEquals('author-123', $author->id);
        $this->assertEquals('John Doe', $author->name);
        $this->assertEquals('JD123', $author->visionId);
        
        // Verify query parameters were sent
        $request = $this->requestHistory[0]['request'];
        $this->assertEquals('name=John%20Doe&page=1&limit=50', $request->getUri()->getQuery());
    }

    public function testNotFoundError(): void
    {
        // Arrange
        $mockResponse = new Response(404, ['Content-Type' => 'application/vnd.api+json'], json_encode([
            'errors' => [
                [
                    'title' => 'Not Found',
                    'detail' => 'The requested author was not found.',
                    'status' => '404'
                ]
            ]
        ]));

        $client = $this->createMockedClient([$mockResponse]);

        // Act & Assert
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Not Found: The requested author was not found.');
        
        $client->authors()->getAuthor('nonexistent-id');
    }

    public function testUnauthorizedError(): void
    {
        // Arrange
        $mockResponse = new Response(401, ['Content-Type' => 'application/vnd.api+json'], json_encode([
            'errors' => [
                [
                    'title' => 'Unauthorized',
                    'detail' => 'Authentication is required.',
                    'status' => '401'
                ]
            ]
        ]));

        $client = $this->createMockedClient([$mockResponse]);

        // Act & Assert
        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('Unauthorized: Authentication is required.');
        
        $client->batches()->createBatch();
    }

    public function testNetworkError(): void
    {
        // Arrange
        $networkError = new RequestException(
            'Connection timeout',
            new Request('GET', '/test')
        );

        $client = $this->createMockedClient([$networkError]);

        // Act & Assert
        $this->expectException(\DvidsApi\Exception\ApiException::class);
        $this->expectExceptionMessage('HTTP request failed: Connection timeout');
        
        $client->authors()->getAuthor('test-id');
    }

    public function testServiceUnitCreateVirin(): void
    {
        // Arrange
        $mockResponse = new Response(201, ['Content-Type' => 'application/vnd.api+json'], json_encode([
            'data' => [
                'id' => 'virin-789',
                'type' => 'service-unit-virin',
                'attributes' => [
                    'virin' => '241001-A-AB123-001',
                    'date' => '2024-10-01T00:00:00Z'
                ],
                'relationships' => [
                    'service_unit' => [
                        'data' => ['id' => 'unit-123', 'type' => 'service-unit']
                    ]
                ]
            ]
        ]));

        $client = $this->createMockedClient([$mockResponse]);

        // Act
        $virinResult = $client->serviceUnits()->createVirin('unit-123', new \DateTime('2024-10-01'));

        // Assert
        $this->assertIsArray($virinResult);
        $this->assertEquals('241001-A-AB123-001', $virinResult['data']['attributes']['virin']);
        
        // Verify request body
        $request = $this->requestHistory[0]['request'];
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('/service-unit/unit-123/virin', $request->getUri()->getPath());
        
        $requestBody = json_decode((string) $request->getBody(), true);
        $this->assertEquals('service-unit-virin', $requestBody['data']['type']);
        $this->assertEquals('2024-10-01T00:00:00+00:00', $requestBody['data']['attributes']['date']);
    }

    public function testCreateAuthorVirin(): void
    {
        // Arrange
        $mockResponse = new Response(201, ['Content-Type' => 'application/vnd.api+json'], json_encode([
            'data' => [
                'id' => 'virin-456',
                'type' => 'author-virin',
                'attributes' => [
                    'virin' => '241001-P-JD123-001',
                    'date' => '2024-10-01T00:00:00Z'
                ],
                'relationships' => [
                    'author' => [
                        'data' => ['id' => 'author-123', 'type' => 'author']
                    ]
                ]
            ]
        ]));

        $client = $this->createMockedClient([$mockResponse]);

        // Act
        $virinResult = $client->authors()->createVirin('author-123', new \DateTime('2024-10-01'));

        // Assert
        $this->assertIsArray($virinResult);
        $this->assertEquals('241001-P-JD123-001', $virinResult['data']['attributes']['virin']);
        
        // Verify request
        $request = $this->requestHistory[0]['request'];
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('/author/author-123/virin', $request->getUri()->getPath());
        
        $requestBody = json_decode((string) $request->getBody(), true);
        $this->assertEquals('author-virin', $requestBody['data']['type']);
    }

    public function testMultipleRequests(): void
    {
        // Arrange - Multiple responses for a workflow
        $batchResponse = new Response(201, [], json_encode(['data' => ['id' => 'batch-123', 'type' => 'batch', 'attributes' => ['closed' => false]]]));
        $uploadResponse = new Response(201, [], json_encode(['data' => ['id' => 'upload-456', 'type' => 'batch-upload', 'attributes' => ['upload_url' => 'https://s3.example.com/upload', 'http_method' => 'PUT', 'use_cdn' => true]]]));
        $virinResponse = new Response(201, [], json_encode(['data' => ['id' => 'virin-789', 'type' => 'service-unit-virin', 'attributes' => ['virin' => '241001-A-AB123-001']]]));

        $client = $this->createMockedClient([$batchResponse, $uploadResponse, $virinResponse]);

        // Act
        $batch = $client->batches()->createBatch();
        $upload = $client->batches()->createBatchUpload($batch->id);
        $virin = $client->serviceUnits()->createVirin('unit-123', new \DateTime('2024-10-01'));

        // Assert
        $this->assertEquals('batch-123', $batch->id);
        $this->assertEquals('upload-456', $upload->id);
        $this->assertEquals('241001-A-AB123-001', $virin['data']['attributes']['virin']);
        
        // Verify all requests were made
        $this->assertCount(3, $this->requestHistory);
        $this->assertEquals('POST', $this->requestHistory[0]['request']->getMethod());
        $this->assertEquals('POST', $this->requestHistory[1]['request']->getMethod());
        $this->assertEquals('POST', $this->requestHistory[2]['request']->getMethod());
    }

    /**
     * Test helper method to verify request structure
     */
    private function assertRequestHasJsonBody(array $expectedData, int $requestIndex = 0): void
    {
        $request = $this->requestHistory[$requestIndex]['request'];
        $body = json_decode((string) $request->getBody(), true);
        
        $this->assertEquals($expectedData, $body);
    }

    /**
     * Test helper method to verify request headers
     */
    private function assertRequestHasHeaders(array $expectedHeaders, int $requestIndex = 0): void
    {
        $request = $this->requestHistory[$requestIndex]['request'];
        
        foreach ($expectedHeaders as $header => $expectedValue) {
            $this->assertEquals($expectedValue, $request->getHeaderLine($header));
        }
    }
}