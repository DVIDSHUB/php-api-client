<?php

declare(strict_types=1);

namespace DvidsApi;

use DvidsApi\Exception\ApiException;
use DvidsApi\Exception\AuthenticationException;
use DvidsApi\Exception\BadRequestException;
use DvidsApi\Exception\ConflictException;
use DvidsApi\Exception\NotFoundException;
use DvidsApi\Exception\UnauthorizedException;
use DvidsApi\Version;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use JsonException;
use Psr\Http\Message\ResponseInterface;

/**
 * DVIDS Content Submission API Client
 * 
 * This client provides access to the DVIDS (Defense Video & Imagery Distribution System)
 * Content Submission API using OAuth2 authentication with authorization code flow.
 */
readonly class DvidsApiClient
{
    public const DEFAULT_BASE_URL = 'https://submitapi.dvidshub.net';
    private const CONTENT_TYPE = 'application/vnd.api+json';
    
    private ClientInterface $httpClient;
    
    public function __construct(
        private string $baseUrl = self::DEFAULT_BASE_URL,
        private ?string $accessToken = null,
        private array $defaultHeaders = [],
        private int $timeout = 30,
        private bool $verifySSL = true,
        ?ClientInterface $httpClient = null
    ) {
        $this->httpClient = $httpClient ?? $this->createDefaultHttpClient();
    }
    
    /**
     * Create default Guzzle HTTP client with appropriate configuration
     */
    private function createDefaultHttpClient(): ClientInterface
    {
        return new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => $this->timeout,
            'verify' => $this->verifySSL,
            'headers' => $this->buildDefaultHeaders(),
            'http_errors' => false, // We'll handle errors ourselves
        ]);
    }
    
    /**
     * Build default headers for all requests
     */
    private function buildDefaultHeaders(): array
    {
        $headers = array_merge([
            'Content-Type' => self::CONTENT_TYPE,
            'Accept' => self::CONTENT_TYPE,
            'User-Agent' => Version::getUserAgent()
        ], $this->defaultHeaders);
        
        if ($this->accessToken !== null) {
            $headers['Authorization'] = 'Bearer ' . $this->accessToken;
        }
        
        return $headers;
    }

    /**
     * Create a new client instance with an access token
     */
    public function withAccessToken(string $accessToken): self
    {
        return new self(
            $this->baseUrl,
            $accessToken,
            $this->defaultHeaders,
            $this->timeout,
            $this->verifySSL
        );
    }

    /**
     * Create a new client instance with custom base URL
     */
    public function withBaseUrl(string $baseUrl): self
    {
        return new self(
            $baseUrl,
            $this->accessToken,
            $this->defaultHeaders,
            $this->timeout,
            $this->verifySSL
        );
    }

    /**
     * Create a new client instance with additional headers
     */
    public function withHeaders(array $headers): self
    {
        return new self(
            $this->baseUrl,
            $this->accessToken,
            array_merge($this->defaultHeaders, $headers),
            $this->timeout,
            $this->verifySSL
        );
    }
    
    /**
     * Create a new client instance with custom HTTP client (useful for testing)
     */
    public function withHttpClient(ClientInterface $httpClient): self
    {
        return new self(
            $this->baseUrl,
            $this->accessToken,
            $this->defaultHeaders,
            $this->timeout,
            $this->verifySSL,
            $httpClient
        );
    }

    /**
     * Make a GET request to the API
     *
     * @param string $endpoint API endpoint path
     * @param array $queryParams Query parameters
     * @return array Decoded JSON response
     * @throws ApiException
     */
    public function get(string $endpoint, array $queryParams = []): array
    {
        try {
            $response = $this->httpClient->request('GET', $endpoint, [
                RequestOptions::QUERY => $queryParams,
                RequestOptions::HEADERS => $this->buildDefaultHeaders(),
            ]);
            
            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw $this->handleGuzzleException($e);
        }
    }

    /**
     * Make a POST request to the API
     *
     * @param string $endpoint API endpoint path
     * @param array $data Request body data
     * @param array $queryParams Query parameters
     * @return array Decoded JSON response
     * @throws ApiException
     */
    public function post(string $endpoint, array $data = [], array $queryParams = []): array
    {
        try {
            $options = [
                RequestOptions::QUERY => $queryParams,
                RequestOptions::HEADERS => $this->buildDefaultHeaders(),
            ];
            
            if (!empty($data)) {
                $options[RequestOptions::JSON] = $data;
            }
            
            $response = $this->httpClient->request('POST', $endpoint, $options);
            
            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw $this->handleGuzzleException($e);
        }
    }

    /**
     * Make a PATCH request to the API
     *
     * @param string $endpoint API endpoint path
     * @param array $data Request body data
     * @param array $queryParams Query parameters
     * @return array Decoded JSON response
     * @throws ApiException
     */
    public function patch(string $endpoint, array $data = [], array $queryParams = []): array
    {
        try {
            $options = [
                RequestOptions::QUERY => $queryParams,
                RequestOptions::HEADERS => $this->buildDefaultHeaders(),
            ];
            
            if (!empty($data)) {
                $options[RequestOptions::JSON] = $data;
            }
            
            $response = $this->httpClient->request('PATCH', $endpoint, $options);
            
            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw $this->handleGuzzleException($e);
        }
    }

    /**
     * Make a PUT request to the API
     *
     * @param string $endpoint API endpoint path
     * @param array $data Request body data
     * @param array $queryParams Query parameters
     * @return array Decoded JSON response
     * @throws ApiException
     */
    public function put(string $endpoint, array $data = [], array $queryParams = []): array
    {
        try {
            $options = [
                RequestOptions::QUERY => $queryParams,
                RequestOptions::HEADERS => $this->buildDefaultHeaders(),
            ];
            
            if (!empty($data)) {
                $options[RequestOptions::JSON] = $data;
            }
            
            $response = $this->httpClient->request('PUT', $endpoint, $options);
            
            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw $this->handleGuzzleException($e);
        }
    }

    /**
     * Make a DELETE request to the API
     *
     * @param string $endpoint API endpoint path
     * @param array $queryParams Query parameters
     * @return array Decoded JSON response
     * @throws ApiException
     */
    public function delete(string $endpoint, array $queryParams = []): array
    {
        try {
            $response = $this->httpClient->request('DELETE', $endpoint, [
                RequestOptions::QUERY => $queryParams,
                RequestOptions::HEADERS => $this->buildDefaultHeaders(),
            ]);
            
            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw $this->handleGuzzleException($e);
        }
    }

    /**
     * Upload a file using PUT method
     *
     * @param string $uploadUrl Pre-signed upload URL
     * @param string $filePath Path to the file to upload
     * @param string $contentType MIME type of the file
     * @param array $additionalHeaders Additional headers for the upload
     * @return array Response from upload
     * @throws ApiException
     */
    public function uploadFile(
        string $uploadUrl, 
        string $filePath, 
        string $contentType, 
        array $additionalHeaders = []
    ): array {
        if (!file_exists($filePath)) {
            throw new Exception("File not found: $filePath");
        }

        $fileResource = fopen($filePath, 'r');
        if ($fileResource === false) {
            throw new Exception("Could not open file: $filePath");
        }

        try {
            $headers = array_merge([
                'Content-Type' => $contentType,
                'Authorization' => null, // Remove authorization, this is just for API calls
            ], $additionalHeaders);

            $response = $this->httpClient->request('PUT', $uploadUrl, [
                RequestOptions::BODY => $fileResource,
                RequestOptions::HEADERS => $headers,
            ]);

            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw $this->handleGuzzleException($e);
        } finally {
            if (is_resource($fileResource)) {
                fclose($fileResource);
            }
        }
    }

    /**
     * Handle Guzzle HTTP response
     */
    private function handleResponse(ResponseInterface $response): array
    {
        $statusCode = $response->getStatusCode();
        $body = (string) $response->getBody();

        if ($statusCode >= 400) {
            $this->handleErrorResponse($statusCode, $body);
        }

        if (empty($body)) {
            return [];
        }

        try {
            return json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new ApiException('Invalid JSON response: ' . $e->getMessage(), $statusCode);
        }
    }
    
    /**
     * Convert Guzzle exceptions to our custom API exceptions
     */
    private function handleGuzzleException(GuzzleException $e): ApiException
    {
        if ($e instanceof RequestException && $e->hasResponse()) {
            $response = $e->getResponse();
            $statusCode = $response->getStatusCode();
            $body = (string) $response->getBody();
            
            try {
                $errorData = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                $errorData = null;
            }
            
            $message = $this->extractErrorMessage($errorData) ?? $e->getMessage();
            
            return match ($statusCode) {
                400 => new BadRequestException($message, $statusCode, $errorData),
                401 => new UnauthorizedException($message, $statusCode, $errorData),
                403 => new AuthenticationException($message, $statusCode, $errorData),
                404 => new NotFoundException($message, $statusCode, $errorData),
                409 => new ConflictException($message, $statusCode, $errorData),
                default => new ApiException($message, $statusCode, $errorData)
            };
        }
        
        return new ApiException('HTTP request failed: ' . $e->getMessage(), 0);
    }


    /**
     * Handle error responses and throw appropriate exceptions
     */
    private function handleErrorResponse(int $statusCode, string $response): never
    {
        $errorData = null;
        try {
            $errorData = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            // Ignore JSON parsing errors for error responses
        }

        $message = $this->extractErrorMessage($errorData) ?? "HTTP $statusCode error";

        match ($statusCode) {
            400 => throw new BadRequestException($message, $statusCode, $errorData),
            401 => throw new UnauthorizedException($message, $statusCode, $errorData),
            403 => throw new AuthenticationException($message, $statusCode, $errorData),
            404 => throw new NotFoundException($message, $statusCode, $errorData),
            409 => throw new ConflictException($message, $statusCode, $errorData),
            default => throw new ApiException($message, $statusCode, $errorData)
        };
    }

    /**
     * Extract error message from API error response
     */
    private function extractErrorMessage(?array $errorData): ?string
    {
        if ($errorData === null || !isset($errorData['errors']) || !is_array($errorData['errors'])) {
            return null;
        }

        $errors = [];
        foreach ($errorData['errors'] as $error) {
            if (isset($error['title'])) {
                $message = $error['title'];
                if (isset($error['detail'])) {
                    $message .= ': ' . $error['detail'];
                }
                $errors[] = $message;
            }
        }

        return empty($errors) ? null : implode('; ', $errors);
    }
}