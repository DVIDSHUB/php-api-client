<?php

declare(strict_types=1);

namespace DvidsApi\Tests;

use DvidsApi\DvidsClient;
use DvidsApi\Model\ServiceUnit;
use DvidsApi\Model\Branch;
use DvidsApi\Resource\ServiceUnitClient;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

/**
 * Tests for ServiceUnitClient
 */
class ServiceUnitClientTest extends TestCase
{
    private array $requestHistory;

    private function createMockedClient(array $responses): DvidsClient
    {
        $this->requestHistory = [];
        
        $mockHandler = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mockHandler);
        $handlerStack->push(Middleware::history($this->requestHistory));
        
        $mockGuzzleClient = new Client(['handler' => $handlerStack]);
        
        return DvidsClient::createWithHttpClient($mockGuzzleClient);
    }

    public function testSearchServiceUnits(): void
    {
        // Arrange
        $mockResponse = new Response(200, ['Content-Type' => 'application/vnd.api+json'], json_encode([
            'data' => [
                [
                    'id' => 'unit-123',
                    'type' => 'service-unit',
                    'attributes' => [
                        'name' => 'US Army Forces Command',
                        'abbreviation' => 'FORSCOM',
                        'branch' => 'army',
                        'dvian' => 'DVIAN123',
                        'requires_publishing_approval' => true
                    ]
                ],
                [
                    'id' => 'unit-456',
                    'type' => 'service-unit', 
                    'attributes' => [
                        'name' => 'US Army Training and Doctrine Command',
                        'abbreviation' => 'TRADOC',
                        'branch' => 'army',
                        'requires_publishing_approval' => false
                    ]
                ]
            ],
            'meta' => [
                'pagination' => [
                    'current_page' => 1,
                    'total_pages' => 1,
                    'total_count' => 2
                ]
            ]
        ]));

        $client = $this->createMockedClient([$mockResponse]);

        // Act
        $result = $client->serviceUnits()->searchServiceUnits([
            'filter[name]' => 'Army',
            'page[size]' => 20
        ]);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertCount(2, $result['data']);
        
        // Verify first service unit
        $firstUnit = $result['data'][0];
        $this->assertEquals('unit-123', $firstUnit['id']);
        $this->assertEquals('US Army Forces Command', $firstUnit['attributes']['name']);
        $this->assertEquals('FORSCOM', $firstUnit['attributes']['abbreviation']);
        $this->assertEquals('army', $firstUnit['attributes']['branch']);
        
        // Verify query parameters were sent
        $request = $this->requestHistory[0]['request'];
        $query = $request->getUri()->getQuery();
        $this->assertStringContainsString('filter%5Bname%5D=Army', $query);
        $this->assertStringContainsString('page%5Bsize%5D=20', $query);
    }

    public function testGetServiceUnit(): void
    {
        // Arrange
        $mockResponse = new Response(200, ['Content-Type' => 'application/vnd.api+json'], json_encode([
            'data' => [
                'id' => 'unit-789',
                'type' => 'service-unit',
                'attributes' => [
                    'name' => 'US Navy Pacific Fleet',
                    'abbreviation' => 'PACFLT',
                    'branch' => 'navy',
                    'dvian' => 'NAVY456',
                    'requires_publishing_approval' => true
                ]
            ]
        ]));

        $client = $this->createMockedClient([$mockResponse]);

        // Act
        $serviceUnit = $client->serviceUnits()->getServiceUnit('unit-789');

        // Assert
        $this->assertInstanceOf(ServiceUnit::class, $serviceUnit);
        $this->assertEquals('unit-789', $serviceUnit->id);
        $this->assertEquals('US Navy Pacific Fleet', $serviceUnit->name);
        $this->assertEquals('PACFLT', $serviceUnit->abbreviation);
        $this->assertEquals(Branch::NAVY, $serviceUnit->branch);
        $this->assertEquals('NAVY456', $serviceUnit->dvian);
        $this->assertTrue($serviceUnit->requiresPublishingApproval);
        
        // Verify request
        $request = $this->requestHistory[0]['request'];
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/service-unit/unit-789', $request->getUri()->getPath());
    }

    public function testCreateVirin(): void
    {
        // Arrange
        $mockResponse = new Response(201, ['Content-Type' => 'application/vnd.api+json'], json_encode([
            'data' => [
                'id' => 'virin-123',
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
        $this->assertArrayHasKey('data', $virinResult);
        $this->assertEquals('241001-A-AB123-001', $virinResult['data']['attributes']['virin']);
        
        // Verify request
        $request = $this->requestHistory[0]['request'];
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('/service-unit/unit-123/virin', $request->getUri()->getPath());
        
        // Verify request body
        $requestBody = json_decode((string) $request->getBody(), true);
        $this->assertEquals('service-unit-virin', $requestBody['data']['type']);
        $this->assertEquals('2024-10-01T00:00:00+00:00', $requestBody['data']['attributes']['date']);
        $this->assertEquals('unit-123', $requestBody['data']['relationships']['service_unit']['data']['id']);
    }

    public function testSearchByName(): void
    {
        // Arrange
        $mockResponse = new Response(200, ['Content-Type' => 'application/vnd.api+json'], json_encode([
            'data' => [
                [
                    'id' => 'unit-air-force',
                    'type' => 'service-unit',
                    'attributes' => [
                        'name' => 'US Air Force Academy',
                        'abbreviation' => 'USAFA',
                        'branch' => 'air_force'
                    ]
                ]
            ]
        ]));

        $client = $this->createMockedClient([$mockResponse]);

        // Act
        $result = $client->serviceUnits()->searchByName('Air Force', 10, 1);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(1, $result['data']);
        
        // Verify query parameters
        $request = $this->requestHistory[0]['request'];
        $query = $request->getUri()->getQuery();
        $this->assertStringContainsString('filter%5Bname%5D=Air%20Force', $query);
        $this->assertStringContainsString('page%5Bsize%5D=10', $query);
        $this->assertStringContainsString('page%5Bnumber%5D=1', $query);
    }

    public function testSearchByAbbreviation(): void
    {
        // Arrange
        $mockResponse = new Response(200, ['Content-Type' => 'application/vnd.api+json'], json_encode([
            'data' => [
                [
                    'id' => 'unit-usmc',
                    'type' => 'service-unit',
                    'attributes' => [
                        'name' => 'US Marine Corps Forces Pacific',
                        'abbreviation' => 'MARFORPAC',
                        'branch' => 'marine_corps'
                    ]
                ]
            ]
        ]));

        $client = $this->createMockedClient([$mockResponse]);

        // Act
        $result = $client->serviceUnits()->searchByAbbreviation('MARFORPAC', 5, 2);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(1, $result['data']);
        
        // Verify query parameters
        $request = $this->requestHistory[0]['request'];
        $query = $request->getUri()->getQuery();
        $this->assertStringContainsString('filter%5Babbreviation%5D=MARFORPAC', $query);
        $this->assertStringContainsString('page%5Bsize%5D=5', $query);
        $this->assertStringContainsString('page%5Bnumber%5D=2', $query);
    }

    public function testSearchByBranch(): void
    {
        // Arrange
        $mockResponse = new Response(200, ['Content-Type' => 'application/vnd.api+json'], json_encode([
            'data' => [
                [
                    'id' => 'unit-navy-1',
                    'type' => 'service-unit',
                    'attributes' => [
                        'name' => 'US Navy Atlantic Fleet',
                        'abbreviation' => 'LANTFLT',
                        'branch' => 'navy'
                    ]
                ],
                [
                    'id' => 'unit-navy-2', 
                    'type' => 'service-unit',
                    'attributes' => [
                        'name' => 'US Navy Pacific Fleet',
                        'abbreviation' => 'PACFLT',
                        'branch' => 'navy'
                    ]
                ]
            ]
        ]));

        $client = $this->createMockedClient([$mockResponse]);

        // Act
        $result = $client->serviceUnits()->searchByBranch('navy', 15, 1);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(2, $result['data']);
        
        // Verify query parameters
        $request = $this->requestHistory[0]['request'];
        $query = $request->getUri()->getQuery();
        $this->assertStringContainsString('filter%5Bbranch%5D=navy', $query);
        $this->assertStringContainsString('page%5Bsize%5D=15', $query);
        $this->assertStringContainsString('page%5Bnumber%5D=1', $query);
    }

    public function testGetServiceUnits(): void
    {
        // Arrange
        $mockResponse = new Response(200, ['Content-Type' => 'application/vnd.api+json'], json_encode([
            'data' => [],
            'meta' => [
                'pagination' => [
                    'current_page' => 2,
                    'total_pages' => 10,
                    'total_count' => 200
                ]
            ]
        ]));

        $client = $this->createMockedClient([$mockResponse]);

        // Act
        $result = $client->serviceUnits()->getServiceUnits(25, 2);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('meta', $result);
        
        // Verify pagination parameters
        $request = $this->requestHistory[0]['request'];
        $query = $request->getUri()->getQuery();
        $this->assertStringContainsString('page%5Bsize%5D=25', $query);
        $this->assertStringContainsString('page%5Bnumber%5D=2', $query);
    }
}