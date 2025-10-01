<?php

declare(strict_types=1);

namespace DvidsApi;

use DvidsApi\Resource\AuthorClient;
use DvidsApi\Resource\BatchClient;
use DvidsApi\Resource\GraphicClient;
use DvidsApi\Resource\PhotoClient;
use DvidsApi\Resource\PublicationClient;
use DvidsApi\Resource\ServiceUnitClient;
use GuzzleHttp\ClientInterface;

/**
 * Main DVIDS API client that provides access to all resource clients
 */
readonly class DvidsClient
{
    private DvidsApiClient $apiClient;
    private AuthorClient $authors;
    private BatchClient $batches;
    private GraphicClient $graphics;
    private PhotoClient $photos;
    private PublicationClient $publications;
    private ServiceUnitClient $serviceUnits;

    public function __construct(
        string $baseUrl = DvidsApiClient::DEFAULT_BASE_URL,
        ?string $accessToken = null,
        array $defaultHeaders = [],
        int $timeout = 30,
        bool $verifySSL = true,
        ?ClientInterface $httpClient = null
    ) {
        $this->apiClient = new DvidsApiClient(
            $baseUrl,
            $accessToken,
            $defaultHeaders,
            $timeout,
            $verifySSL,
            $httpClient
        );

        $this->authors = new AuthorClient($this->apiClient);
        $this->batches = new BatchClient($this->apiClient);
        $this->graphics = new GraphicClient($this->apiClient);
        $this->photos = new PhotoClient($this->apiClient);
        $this->publications = new PublicationClient($this->apiClient);
        $this->serviceUnits = new ServiceUnitClient($this->apiClient);
    }

    /**
     * Create a new client instance with an access token
     */
    public function withAccessToken(string $accessToken): self
    {
        return new self(
            DvidsApiClient::DEFAULT_BASE_URL,
            $accessToken
        );
    }

    /**
     * Get the underlying API client for direct access
     */
    public function getApiClient(): DvidsApiClient
    {
        return $this->apiClient;
    }
    
    /**
     * Create a new client instance with custom HTTP client (useful for testing)
     */
    public static function createWithHttpClient(ClientInterface $httpClient): self
    {
        // Create API client with custom HTTP client
        $apiClient = new DvidsApiClient(httpClient: $httpClient);
        
        // Create a new DvidsClient that uses this API client
        return new self(
            baseUrl: DvidsApiClient::DEFAULT_BASE_URL,
            accessToken: null,
            defaultHeaders: [],
            timeout: 30,
            verifySSL: true,
            httpClient: $httpClient
        );
    }

    /**
     * Get the authors resource client
     */
    public function authors(): AuthorClient
    {
        return $this->authors;
    }

    /**
     * Get the batches resource client
     */
    public function batches(): BatchClient
    {
        return $this->batches;
    }
    
    /**
     * Get the graphics resource client
     */
    public function graphics(): GraphicClient
    {
        return $this->graphics;
    }
    
    /**
     * Get the photos resource client
     */
    public function photos(): PhotoClient
    {
        return $this->photos;
    }
    
    /**
     * Get the publications resource client
     */
    public function publications(): PublicationClient
    {
        return $this->publications;
    }
    
    /**
     * Get the service units resource client
     */
    public function serviceUnits(): ServiceUnitClient
    {
        return $this->serviceUnits;
    }

    /**
     * Create an OAuth2 authorization URL
     * 
     * @param string $clientId Your OAuth2 client ID
     * @param string $redirectUri The redirect URI registered with your application
     * @param array $scopes The scopes to request (basic, email, upload)
     * @param string $state Optional state parameter for CSRF protection
     * @return string The authorization URL
     */
    public function getAuthorizationUrl(
        string $clientId,
        string $redirectUri,
        array $scopes = ['basic', 'email', 'upload'],
        ?string $state = null
    ): string {
        $params = [
            'response_type' => 'code',
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'scope' => implode(' ', $scopes)
        ];

        if ($state !== null) {
            $params['state'] = $state;
        }

        return 'https://api.dvidshub.net/auth/authorize?' . http_build_query($params);
    }

    /**
     * Exchange authorization code for access token
     * 
     * Note: This method makes a request to the token endpoint, not the API base URL
     * 
     * @param string $code Authorization code from the callback
     * @param string $clientId Your OAuth2 client ID
     * @param string $clientSecret Your OAuth2 client secret
     * @param string $redirectUri The same redirect URI used in the authorization request
     * @return array Token response containing access_token, token_type, expires_in, etc.
     */
    public function exchangeAuthorizationCode(
        string $code,
        string $clientId,
        string $clientSecret,
        string $redirectUri
    ): array {
        $data = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri
        ];

        // Use the API client's post method but we need to encode the data as form data
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query($data),
                'timeout' => 30
            ]
        ]);

        $response = file_get_contents('https://api.dvidshub.net/auth/access_token', false, $context);
        
        if ($response === false) {
            throw new \RuntimeException('Failed to exchange authorization code');
        }

        $tokenData = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

        return $tokenData;
    }

    /**
     * Create a client with OAuth2 authentication flow
     * 
     * This is a helper method that combines authorization URL generation and token exchange
     * 
     * @param string $clientId Your OAuth2 client ID  
     * @param string $redirectUri The redirect URI registered with your application
     * @param array $scopes The scopes to request
     * @return array Array containing 'authorization_url' and a callable 'exchange_token' function
     */
    public function createOAuth2Flow(
        string $clientId,
        string $redirectUri,
        array $scopes = ['basic', 'email', 'upload']
    ): array {
        $state = bin2hex(random_bytes(16)); // Generate a random state for CSRF protection
        
        $authUrl = $this->getAuthorizationUrl($clientId, $redirectUri, $scopes, $state);
        
        return [
            'authorization_url' => $authUrl,
            'state' => $state,
            'exchange_token' => function (string $code, string $clientSecret, string $receivedState = null) 
                use ($clientId, $redirectUri, $state): array {
                
                if ($receivedState !== null && $receivedState !== $state) {
                    throw new \RuntimeException('Invalid state parameter - possible CSRF attack');
                }
                
                return $this->exchangeAuthorizationCode($code, $clientId, $clientSecret, $redirectUri);
            }
        ];
    }
}