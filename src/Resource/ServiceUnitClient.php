<?php

declare(strict_types=1);

namespace DvidsApi\Resource;

use DvidsApi\DvidsApiClient;
use DvidsApi\Model\ServiceUnit;

/**
 * Client for managing service unit resources
 */
readonly class ServiceUnitClient
{
    public function __construct(
        private DvidsApiClient $client
    ) {
    }

    /**
     * Search for service units
     *
     * @param array $queryParams Search parameters (e.g., 'name', 'page', 'limit')
     * @return array Response containing service unit data
     */
    public function searchServiceUnits(array $queryParams = []): array
    {
        return $this->client->get('/service-unit', $queryParams);
    }

    /**
     * Get a specific service unit by ID
     *
     * @param string $serviceUnitId Service unit ID
     * @return ServiceUnit Service unit object
     */
    public function getServiceUnit(string $serviceUnitId): ServiceUnit
    {
        $response = $this->client->get("/service-unit/{$serviceUnitId}");
        
        if (!isset($response['data'])) {
            throw new \InvalidArgumentException('Invalid service unit response format');
        }

        return ServiceUnit::fromArray($response['data']);
    }

    /**
     * Generate a service unit VIRIN
     *
     * @param string $serviceUnitId Service unit ID
     * @param \DateTimeInterface $date Date for the VIRIN
     * @return array VIRIN response containing the generated VIRIN string
     */
    public function createVirin(string $serviceUnitId, \DateTimeInterface $date): array
    {
        $data = [
            'data' => [
                'type' => 'service-unit-virin',
                'attributes' => [
                    'date' => $date->format('c')
                ],
                'relationships' => [
                    'service_unit' => [
                        'data' => [
                            'id' => $serviceUnitId,
                            'type' => 'service-unit'
                        ]
                    ]
                ]
            ]
        ];

        return $this->client->post("/service-unit/{$serviceUnitId}/virin", $data);
    }

    /**
     * Search for service units by name (convenience method)
     *
     * @param string $name Service unit name to search for
     * @param int $pageSize Number of results per page (default: 20)
     * @param int $pageNumber Page number (default: 0)
     * @return array Response containing matching service units
     */
    public function searchByName(string $name, int $pageSize = 20, int $pageNumber = 0): array
    {
        return $this->searchServiceUnits([
            'name' => $name,
            'limit' => $pageSize,
            'page' => $pageNumber
        ]);
    }

    /**
     * Search for service units by branch (convenience method)
     *
     * @param string $branch Branch code (e.g., 'army', 'navy', 'air_force')
     * @param int $pageSize Number of results per page (default: 20)
     * @param int $pageNumber Page number (default: 0)
     * @return array Response containing matching service units
     */
    public function searchByBranch(string $branch, int $pageSize = 20, int $pageNumber = 0): array
    {
        return $this->searchServiceUnits([
            'branch' => $branch,
            'limit' => $pageSize,
            'page' => $pageNumber
        ]);
    }

    /**
     * Get service units with pagination helper
     *
     * @param int $pageSize Number of results per page (default: 20)
     * @param int $pageNumber Page number (default: 0)
     * @return array Response containing service units for the specified page
     */
    public function getServiceUnits(int $pageSize = 20, int $pageNumber = 0): array
    {
        return $this->searchServiceUnits([
            'limit' => $pageSize,
            'page' => $pageNumber
        ]);
    }

    /**
     * Get the caller's service units with pagination helper
     *
     * @param int $pageSize Number of results per page (default: 20)
     * @param int $pageNumber Page number (default: 0)
     * @return array Response containing service units for the specified page
     */
    public function getMyServiceUnits(int $pageSize = 20, int $pageNumber = 0): array
    {
        return $this->searchServiceUnits([
            'only_mine' => true,
            'limit' => $pageSize,
            'page' => $pageNumber
        ]);
    }
}