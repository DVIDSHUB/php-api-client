<?php

declare(strict_types=1);

namespace DvidsApi\Tests;

use DvidsApi\Version;
use DvidsApi\DvidsClient;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

/**
 * Tests for version management and User-Agent header
 */
class VersionTest extends TestCase
{
    public function testVersionConstantExists(): void
    {
        $this->assertIsString(Version::SDK_VERSION);
        $this->assertNotEmpty(Version::SDK_VERSION);
        
        // Verify it follows semantic versioning pattern (major.minor.patch)
        $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+$/', Version::SDK_VERSION);
    }
    
    public function testGetUserAgentReturnsCorrectFormat(): void
    {
        $userAgent = Version::getUserAgent();
        
        $this->assertIsString($userAgent);
        $this->assertStringStartsWith('DVIDS-PHP-Client/', $userAgent);
        $this->assertStringContainsString(Version::SDK_VERSION, $userAgent);
        
        // Verify full format
        $expectedUserAgent = 'DVIDS-PHP-Client/' . Version::SDK_VERSION;
        $this->assertEquals($expectedUserAgent, $userAgent);
    }
    
    public function testUserAgentIsUsedInHttpRequests(): void
    {
        // Arrange
        $requestHistory = [];
        $mockResponse = new Response(200, [], json_encode(['data' => []]));
        $mockHandler = new MockHandler([$mockResponse]);
        $handlerStack = HandlerStack::create($mockHandler);
        $handlerStack->push(Middleware::history($requestHistory));
        
        $mockGuzzleClient = new Client(['handler' => $handlerStack]);
        $client = DvidsClient::createWithHttpClient($mockGuzzleClient);
        
        // Act - Make any API call
        try {
            $client->authors()->searchByName('test');
        } catch (\Exception $e) {
            // Ignore exceptions, we just want to capture the request
        }
        
        // Assert
        $this->assertCount(1, $requestHistory);
        $request = $requestHistory[0]['request'];
        
        $userAgentHeader = $request->getHeaderLine('User-Agent');
        $this->assertEquals(Version::getUserAgent(), $userAgentHeader);
        $this->assertStringStartsWith('DVIDS-PHP-Client/', $userAgentHeader);
        $this->assertStringContainsString(Version::SDK_VERSION, $userAgentHeader);
    }
    
    public function testVersionFormatIsValidSemver(): void
    {
        $version = Version::SDK_VERSION;
        
        // Test that version has exactly 3 parts separated by dots
        $parts = explode('.', $version);
        $this->assertCount(3, $parts, 'Version should have exactly 3 parts (major.minor.patch)');
        
        // Test that each part is numeric
        foreach ($parts as $i => $part) {
            $this->assertTrue(ctype_digit($part), "Version part $i ('$part') should be numeric");
            $this->assertGreaterThanOrEqual(0, (int)$part, "Version part $i should be >= 0");
        }
        
        // Test that major version is not empty
        $this->assertGreaterThanOrEqual(0, (int)$parts[0], 'Major version should be >= 0');
    }
    
    public function testUserAgentHeaderIsPresentInAllClientMethods(): void
    {
        // Test multiple API methods to ensure User-Agent is always included
        $requestHistory = [];
        
        // Create multiple mock responses for different API calls
        $mockResponses = [
            new Response(201, [], json_encode(['data' => ['id' => 'batch-123', 'type' => 'batch']])),
            new Response(200, [], json_encode(['data' => []])),
            new Response(201, [], json_encode(['data' => ['id' => 'virin-123', 'type' => 'service-unit-virin']])),
        ];
        
        $mockHandler = new MockHandler($mockResponses);
        $handlerStack = HandlerStack::create($mockHandler);
        $handlerStack->push(Middleware::history($requestHistory));
        
        $mockGuzzleClient = new Client(['handler' => $handlerStack]);
        $client = DvidsClient::createWithHttpClient($mockGuzzleClient);
        
        // Act - Make various API calls
        try {
            $client->batches()->createBatch();
            $client->authors()->searchByName('test');
            $client->serviceUnits()->createVirin('unit-123', new \DateTime());
        } catch (\Exception $e) {
            // Ignore exceptions, we just want to capture the requests
        }
        
        // Assert - All requests should have the User-Agent header
        $this->assertCount(3, $requestHistory);
        
        $expectedUserAgent = Version::getUserAgent();
        foreach ($requestHistory as $i => $transaction) {
            $request = $transaction['request'];
            $userAgentHeader = $request->getHeaderLine('User-Agent');
            
            $this->assertEquals(
                $expectedUserAgent,
                $userAgentHeader,
                "Request $i should have correct User-Agent header"
            );
        }
    }
    
    public function testAllDefaultHeadersArePresentInRequests(): void
    {
        // Test that all default headers (Content-Type, Accept, User-Agent) are present
        $requestHistory = [];
        
        $mockResponse = new Response(201, [], json_encode([
            'data' => ['id' => 'batch-123', 'type' => 'batch']
        ]));
        
        $mockHandler = new MockHandler([$mockResponse]);
        $handlerStack = HandlerStack::create($mockHandler);
        $handlerStack->push(Middleware::history($requestHistory));
        
        $mockGuzzleClient = new Client(['handler' => $handlerStack]);
        $client = DvidsClient::createWithHttpClient($mockGuzzleClient);
        
        // Act - Make a POST request with JSON data
        try {
            $client->batches()->createBatch();
        } catch (\Exception $e) {
            // Ignore exceptions, we just want to capture the request
        }
        
        // Assert
        $this->assertCount(1, $requestHistory);
        $request = $requestHistory[0]['request'];
        
        // Check all expected headers are present
        $this->assertEquals('application/vnd.api+json', $request->getHeaderLine('Content-Type'));
        $this->assertEquals('application/vnd.api+json', $request->getHeaderLine('Accept'));
        $this->assertEquals(Version::getUserAgent(), $request->getHeaderLine('User-Agent'));
    }
}