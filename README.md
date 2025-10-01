# DVIDS API Client for PHP

A modern PHP client library for the DVIDS (Defense Video & Imagery Distribution System) Content Submission API, built with PHP 8.2+ features including readonly classes, enums, named parameters, and strong typing.

## Features

- **Modern PHP 8.2+ Syntax**: Utilizes readonly classes, enums, union types, and named parameters
- **OAuth2 Authentication**: Full support for DVIDS OAuth2 authentication flow
- **Type Safety**: Comprehensive type hints and data models for all API responses
- **Immutable Design**: Immutable client instances for thread safety
- **Resource Clients**: Dedicated clients for different API resources (Authors, Batches, etc.)
- **File Uploads**: Support for both simple and multipart file uploads
- **Error Handling**: Specific exception types for different HTTP status codes
- **PSR-4 Autoloading**: Standard PHP autoloading support

## Installation

```bash
composer require dvids/api-client "^0.0"
```

### Requirements

- PHP 8.2 or higher
- `ext-json` extension
- `ext-openssl` extension

## Quick Start

### Basic Usage

### OAuth2 Authentication

```php
<?php
require_once 'vendor/autoload.php';

use DvidsApi\DvidsClient;

$client = new DvidsClient();

// Step 1: Create authorization URL
$oauth2Flow = $client->createOAuth2Flow(
    clientId: 'your-client-id',
    redirectUri: 'https://your-app.com/callback',
    scopes: ['basic', 'email', 'upload']
);

// Redirect user to authorization URL
header('Location: ' . $oauth2Flow['authorization_url']);

// Step 2: Handle callback (in your callback handler)
if (isset($_GET['code']) && isset($_GET['state'])) {
    $tokenResponse = $oauth2Flow['exchange_token'](
        code: $_GET['code'],
        clientSecret: 'your-client-secret',
        receivedState: $_GET['state']
    );
    
    // Store these securely
    $accessToken = $tokenResponse['access_token'];
    $refreshToken = $tokenResponse['refresh_token'];
}
```

```php
// Add your access token from OAuth2 flow (see example below)
$authenticatedClient = $client->withAccessToken('your-access-token');

// Get 5 units matching the name Army
$unitSearchResults = $authenticatedClient->serviceUnits()->searchByName('Army', 5);

if (!empty($unitSearchResults['data'])) {
    // Pick the first one
    $unit = \DvidsApi\Model\ServiceUnit::fromArray($unitSearchResults['data'][0]);
    echo "{$unit->name} ({$unit->abbreviation}) - Branch: {$unit->branch->value}\n";
}

// Simplified workflow - handles batch creation and upload internally
$photo = $authenticatedClient->photos()->createCompletePhotoWorkflowWithServiceUnitVirin(
    '/path/to/photo.jpg',           // imageFilePath
    $unit->id,                      // serviceUnitId
    new DateTimeImmutable('2024-10-01'),     // createdAt
    'Training Exercise',            // title
    'Military training exercise.',  // description
    'Cleared for public release by dod-media-service@defense.gov.',  // instructions
    ['training', 'military'],       // tags
    [], // authorIds
    'DE' // country code
);

// See https://api.dvidshub.net/docs/dynamic_thumbnails
$thumbnailUrl = str_replace('{thumbnailSpec}', '400w_q95', $photo->thumbnailUrlTemplate);

echo "{$photo->id}: {$thumbnailUrl}\n";
```

### Working with ServiceUnits
```php
try {
    echo "=== ServiceUnit Client Example ===\n\n";
    
    // 1. Search for service units by name
    echo "1. Searching for service units by name 'Army':\n";
    $searchResults = $authenticatedClient->serviceUnits()->searchByName('Army', 5);
    
    if (!empty($searchResults['data'])) {
        foreach ($searchResults['data'] as $unitData) {
            $unit = \DvidsApi\Model\ServiceUnit::fromArray($unitData);
            echo "  - {$unit->name} ({$unit->abbreviation}) - Branch: {$unit->branch->value}\n";
        }
    } else {
        echo "  No units found\n";
    }
    echo "\n";
    
    // 2. Search by abbreviation
    echo "2. Searching for service units by abbreviation 'USN':\n";
    $abbreviationResults = $authenticatedClient->serviceUnits()->searchByAbbreviation('USN', 3);
    
    if (!empty($abbreviationResults['data'])) {
        foreach ($abbreviationResults['data'] as $unitData) {
            $unit = \DvidsApi\Model\ServiceUnit::fromArray($unitData);
            echo "  - {$unit->name} ({$unit->abbreviation}) - ID: {$unit->id}\n";
        }
    } else {
        echo "  No units found\n";
    }
    echo "\n";
    
    // 3. Search by branch
    echo "3. Searching for service units by branch 'navy':\n";
    $branchResults = $authenticatedClient->serviceUnits()->searchByBranch('navy', 5);
    
    if (!empty($branchResults['data'])) {
        foreach ($branchResults['data'] as $unitData) {
            $unit = \DvidsApi\Model\ServiceUnit::fromArray($unitData);
            echo "  - {$unit->name} ({$unit->abbreviation})\n";
        }
    } else {
        echo "  No units found\n";
    }
    echo "\n";
    
    // 4. Get a specific service unit by ID
    if (!empty($searchResults['data'])) {
        $firstUnitId = $searchResults['data'][0]['id'];
        echo "4. Getting specific service unit by ID '{$firstUnitId}':\n";
        
        $specificUnit = $authenticatedClient->serviceUnits()->getServiceUnit($firstUnitId);
        echo "  - Name: {$specificUnit->name}\n";
        echo "  - Abbreviation: {$specificUnit->abbreviation}\n";
        echo "  - Branch: {$specificUnit->branch->value}\n";
        echo "  - DVIAN: " . ($specificUnit->dvian ?? 'N/A') . "\n";
        echo "  - Requires Publishing Approval: " . ($specificUnit->requiresPublishingApproval ? 'Yes' : 'No') . "\n";
        echo "\n";
        
        // 5. Generate a VIRIN using this service unit
        echo "5. Generating VIRIN for service unit '{$specificUnit->abbreviation}':\n";
        $virinResult = $authenticatedClient->serviceUnits()->createVirin($firstUnitId, new DateTime('2024-10-01'));
        
        $virin = $virinResult['data']['attributes']['virin'];
        echo "  - Generated VIRIN: {$virin}\n";
        echo "  - Date used: 2024-10-01\n";
        echo "\n";
    }
    
    // 6. General service unit listing with pagination
    echo "6. Getting service units with pagination (page 1, 10 per page):\n";
    $paginatedResults = $authenticatedClient->serviceUnits()->getServiceUnits(10, 1);
    
    echo "  - Total results on this page: " . count($paginatedResults['data'] ?? []) . "\n";
    if (isset($paginatedResults['meta']['pagination'])) {
        $pagination = $paginatedResults['meta']['pagination'];
        echo "  - Current page: {$pagination['current_page']}\n";
        echo "  - Total pages: {$pagination['total_pages']}\n";
        echo "  - Total items: {$pagination['total_count']}\n";
    }
    echo "\n";
    
    // 7. Raw search with custom parameters
    echo "7. Raw search with custom parameters:\n";
    $customSearch = $authenticatedClient->serviceUnits()->searchServiceUnits([
        'filter[branch]' => 'air_force',
        'page[size]' => 3,
        'page[number]' => 1,
        'sort' => 'name'
    ]);
    
    if (!empty($customSearch['data'])) {
        echo "  - Found " . count($customSearch['data']) . " Air Force units (sorted by name):\n";
        foreach ($customSearch['data'] as $unitData) {
            $unit = \DvidsApi\Model\ServiceUnit::fromArray($unitData);
            echo "    * {$unit->name} ({$unit->abbreviation})\n";
        }
    }
    
} catch (ApiException $e) {
    echo "API Error: {$e->getMessage()}\n";
    echo "Status Code: {$e->getStatusCode()}\n";
    
    if ($e->getErrorData()) {
        echo "Error Details:\n";
        print_r($e->getErrorData());
    }
} catch (Exception $e) {
    echo "Error: {$e->getMessage()}\n";
}
```

### Working with Authors

```php
// Search for authors
$results = $authenticatedClient->authors()->searchByName('John Smith');

foreach ($results['data'] as $author) {
    echo "Author: {$author->name}\n";
    echo "Vision ID: {$author->visionId}\n";
    
    if ($author->jobGrade) {
        echo "Rank: {$author->jobGrade->name} ({$author->jobGrade->abbreviation})\n";
        echo "Branch: {$author->jobGrade->branch->getDisplayName()}\n";
    }
}

// Get specific author
$author = $authenticatedClient->authors()->getAuthor('author-id');

// Create VIRIN for author
$virin = $authenticatedClient->authors()->createVirin(
    authorId: 'author-id',
    date: new DateTimeImmutable()
);
```

### File Upload Workflow with BatchUpload

The SDK uses a BatchUpload system with presigned URLs for secure file uploads:
```

#### Upload a photo
```php
// Create batch
$batch = $authenticatedClient->batches()->createBatch();

// Create batch upload and upload file in one step
$uploadResult = $authenticatedClient->batches()->createAndUploadFile(
    batchId: $batch->id,
    filePath: '/path/to/photo.jpg',
    contentType: 'image/jpeg'
);

$batchUploadId = $uploadResult['batch_upload']->id;
```

### Working with Graphics

```php
// Get available graphic categories
$categories = $authenticatedClient->graphics()->getGraphicCategories();

// Create a batch and upload a graphic file
$batch = $authenticatedClient->batches()->createBatch();
$uploadResult = $authenticatedClient->batches()->createAndUploadFile($batch->id, '/path/to/design.png', 'image/png');

// Create graphic with metadata
$graphic = $authenticatedClient->graphics()->createSimpleGraphic(
    batchId: $batch->id,
    title: 'Military Infographic',
    description: 'Educational infographic about procedures',
    instructions: 'Cleared for public release',
    createdAt: new DateTimeImmutable(),
    virin: '241001-A-AB123-1001',
    country: 'US',
    batchUploadId: $uploadResult['batch_upload']->id,
    categoryId: $categories['data'][0]['id'],
    serviceUnitId: 'service-unit-id',
    tags: ['education', 'procedures']
);

echo "Created graphic: {$graphic->title} (Status: {$graphic->status?->getDisplayName()})";
```

### Working with Publications

```php
// Search for available publications
$publications = $authenticatedClient->publications()->searchByTitle('Engineer Magazine');

// Create a batch and upload PDF with publication issue in one step
$batch = $authenticatedClient->batches()->createBatch();
$result = $authenticatedClient->publications()->createPublicationIssueWithUpload(
    batchId: $batch->id,
    pdfFilePath: '/path/to/issue.pdf',
    description: 'October 2024 Engineering Publication',
    createdAt: new DateTimeImmutable(),
    publicationId: $publications['data'][0]->id,
    batchClient: $authenticatedClient->batches()
);

$publicationIssue = $result['publication_issue'];
echo "Created publication issue: {$publicationIssue->description}";
```

## API Reference

### Main Client

#### `DvidsClient`

The main client class that provides access to all resource clients.

**Constructor:**
```php
new DvidsClient(
    string $baseUrl = 'https://submitapi.dvidshub.net',
    ?string $accessToken = null,
    array $defaultHeaders = [],
    int $timeout = 30,
    bool $verifySSL = true
)
```

**Methods:**
- `withAccessToken(string $accessToken): self` - Create client with access token
- `authors(): AuthorClient` - Get the authors resource client
- `batches(): BatchClient` - Get the batches resource client
- `graphics(): GraphicClient` - Get the graphics resource client
- `photos(): PhotoClient` - Get the photos resource client
- `publications(): PublicationClient` - Get the publications resource client
- `serviceUnits(): ServiceUnitClient` - Get the service-unit resource client
- `getApiClient(): DvidsApiClient` - Get the underlying API client

### Resource Clients

#### `AuthorClient`

Methods for working with author resources:

- `getAuthors(array $filters = [], int $page = 1, int $limit = 50): array`
- `getAuthor(string $id): Author`
- `searchByName(string $name, int $page = 1, int $limit = 50): array`
- `searchByFirstName(string $firstName, int $page = 1, int $limit = 50): array`
- `searchByLastName(string $lastName, int $page = 1, int $limit = 50): array`
- `findByVisionId(string $visionId): array`
- `filterByBranch(string $branch, int $page = 1, int $limit = 50): array`
- `createVirin(string $authorId, DateTimeInterface $date): array`

#### `BatchClient`

Methods for managing batches and file uploads:

**Basic Batch Operations:**
- `createBatch(): Batch` - Create a new batch
- `getBatch(string $id): Batch` - Get batch by ID
- `closeBatch(string $id, bool $sendConfirmationEmail = true): Batch` - Submit batch for approval

**File Upload Operations:**
- `createBatchUpload(string $batchId, bool $useCdn = true): BatchUpload` - Create batch upload with presigned URL
- `uploadFile(BatchUpload $batchUpload, string $filePath, string $contentType): array` - Upload file using BatchUpload object
- `createAndUploadFile(string $batchId, string $filePath, string $contentType, bool $useCdn = true): array` - Create batch upload and upload file in one step

**Multipart Upload Operations (for large files):**
- `createBatchMultipartUpload(string $batchId, string $contentType): array`
- `createBatchMultipartUploadPart(string $batchId, string $multipartUploadId, int $partNumber): array`
- `completeBatchMultipartUpload(string $batchId, string $multipartUploadId, array $parts): array`

#### `GraphicClient`

Methods for managing graphic resources:

- `getGraphicCategories(int $page = 1, int $limit = 50): array`
- `createBatchGraphic(string $batchId, array $graphicData): Graphic`
- `getBatchGraphic(string $batchId, string $graphicId): Graphic`
- `updateBatchGraphic(string $batchId, string $graphicId, array $graphicData): Graphic`
- `deleteBatchGraphic(string $batchId, string $graphicId): bool`
- `createSimpleGraphic(string $batchId, string $title, string $description, string $instructions, DateTimeInterface $createdAt, string $virin, string $country, string $batchUploadId, string $categoryId, string $serviceUnitId, array $tags = [], ?string $subdiv = null, ?string $city = null, ?string $captionWriter = null, array $authorIds = [], array $themeIds = []): Graphic`

#### `PhotoClient`

Methods for managing photo resources:

**Complete Workflows:**
- `createCompletePhotoWorkflow(string $batchId, string $imageFilePath, string $contentType, ...): array` - Upload image, generate service unit VIRIN, create photo
- `createCompletePhotoWorkflowWithAuthorVirin(string $batchId, string $imageFilePath, string $contentType, ..., string $authorId, ...): array` - Upload image, generate author VIRIN, create photo
- `createPhotoWithServiceUnitGeneratedVirin(string $batchId, string $title, ..., string $serviceUnitId, ...): Photo` - Create photo with auto-generated service unit VIRIN
- `createPhotoWithAuthorGeneratedVirin(string $batchId, string $title, ..., string $authorId, ...): Photo` - Create photo with auto-generated author VIRIN

**Individual Operations:**
- `createSimplePhoto(string $batchId, string $title, ..., string $virin, string $batchUploadId, ?string $serviceUnitId, ...): Photo` - Create photo with provided VIRIN
- `createBatchPhoto(string $batchId, array $photoData): Photo` - Create photo with raw data
- `getBatchPhoto(string $batchId, string $photoId): Photo` - Get photo from batch
- `updateBatchPhoto(string $batchId, string $photoId, array $photoData): Photo` - Update photo
- `deleteBatchPhoto(string $batchId, string $photoId): bool` - Delete photo

#### `PublicationClient`

Methods for managing publication and publication issue resources:

- `getPublications(array $filters = [], int $page = 1, int $limit = 50): array`
- `searchByTitle(string $title, int $page = 1, int $limit = 50): array`
- `createBatchPublicationIssue(string $batchId, array $publicationIssueData): PublicationIssue`
- `getBatchPublicationIssue(string $batchId, string $publicationIssueId): PublicationIssue`
- `updateBatchPublicationIssue(string $batchId, string $publicationIssueId, array $publicationIssueData): PublicationIssue`
- `deleteBatchPublicationIssue(string $batchId, string $publicationIssueId): bool`
- `createSimplePublicationIssue(string $batchId, string $description, DateTimeInterface $createdAt, string $publicationId, string $batchUploadId): PublicationIssue`
- `createPublicationIssueWithUpload(string $batchId, string $pdfFilePath, string $description, DateTimeInterface $createdAt, string $publicationId, BatchClient $batchClient): array`

### Data Models

All data models are readonly classes with type-safe properties:

#### `Author`
- `string $id`
- `string $name`
- `?string $visionId`
- `?JobGrade $jobGrade`
- `ServiceUnitReference[] $serviceUnits`

#### `JobGrade`
- `string $name`
- `string $associatedPressName`
- `string $abbreviation`
- `?Branch $branch`
- `?string $jobGrade`
- `?string $natoCode`
- `?string $countryCode`

#### `Branch` (Enum)
- `ARMY = 'army'`
- `NAVY = 'navy'`
- `AIR_FORCE = 'air-force'`
- `MARINES = 'marines'`
- `COAST_GUARD = 'coast-guard'`
- `SPACE_FORCE = 'space-force'`
- `JOINT = 'joint'`
- `CIVILIAN = 'civilian'`

#### `Graphic`
- `string $id`
- `string $title`
- `string $description`
- `string $instructions`
- `DateTimeImmutable $createdAt`
- `string $virin`
- `string $country`
- `string[] $tags`
- `?string $subdiv`
- `?string $city`
- `?GraphicStatus $status`
- `?string $captionWriter`
- `AuthorReference[] $authors`
- `?BatchUploadReference $batchUpload`
- `?GraphicCategoryReference $category`
- `?ServiceUnitReference $serviceUnit`
- `ThemeReference[] $themes`

#### `Photo`
- `string $id`
- `string $title`
- `string $description`
- `string $instructions`
- `DateTimeImmutable $createdAt`
- `string $virin`
- `string $country`
- `string[] $tags`
- `?string $subdiv`
- `?string $city`
- `?PhotoStatus $status`
- `?string $captionWriter`
- `?string $jobIdentifier`
- `?string $operationName`
- `?string $thumbnailUrlTemplate`
- `AuthorReference[] $authors`
- `?BatchUploadReference $batchUpload`
- `?ServiceUnitReference $serviceUnit`
- `ThemeReference[] $themes`

#### `Publication`
- `string $id`
- `string $title`
- `string $description`
- `DateTimeImmutable $createdAt`
- `?ServiceUnitReference $serviceUnit`

#### `PublicationIssue`
- `string $id`
- `string $description`
- `DateTimeImmutable $createdAt`
- `?PublicationIssueStatus $status`
- `?PublicationReference $publication`
- `?BatchUploadReference $batchUpload`

#### `GraphicStatus` (Enum)
- `UPLOADED = 'uploaded'`
- `PENDING_PROCESSING = 'pending-processing'`
- `NEEDS_APPROVAL = 'needs-approval'`
- `PUBLISHED = 'published'`
- `ARCHIVED = 'archived'`

#### `PhotoStatus` (Enum)
- `UPLOADED = 'uploaded'`
- `PENDING_PROCESSING = 'pending-processing'`
- `NEEDS_APPROVAL = 'needs-approval'`
- `PUBLISHED = 'published'`
- `ARCHIVED = 'archived'`

#### `PublicationIssueStatus` (Enum)
- `UPLOADED = 'uploaded'`
- `PENDING_PROCESSING = 'pending-processing'`
- `NEEDS_APPROVAL = 'needs-approval'`
- `PUBLISHED = 'published'`

#### `BatchUpload`

Represents a batch upload with presigned URLs for file uploads.

**Properties:**
- `id: string` - Unique identifier for the batch upload
- `uploadUrl: string` - Presigned URL for uploading the file
- `httpMethod: string` - HTTP method to use (usually 'PUT')
- `useCdn: bool` - Whether CDN acceleration is enabled
- `multipartFormUploadParams: ?array` - Form parameters for POST uploads
- `batchId: ?string` - ID of the parent batch

**Methods:**
- `isMultipartFormUpload(): bool` - Check if it's a POST multipart upload
- `isPutUpload(): bool` - Check if it's a simple PUT upload
- `fromArray(array $data): self` - Create from API response
- `toArray(): array` - Convert to array format

**Supported File Types:**
- Images: JPEG, PNG, GIF
- Documents: PDF
- Archives: ZIP
- Video: MP4
- Design Files: INDD, PSD, AI

### Error Handling

The client provides specific exception types for different error conditions:

```php
use DvidsApi\Exception\{
    ApiException,
    BadRequestException,
    UnauthorizedException,
    AuthenticationException,
    NotFoundException,
    ConflictException
};

try {
    $author = $client->authors()->getAuthor('non-existent-id');
} catch (NotFoundException $e) {
    echo "Author not found: {$e->getMessage()}";
} catch (UnauthorizedException $e) {
    echo "Authentication required: {$e->getMessage()}";
    // Redirect to login or refresh token
} catch (ApiException $e) {
    echo "API Error: {$e->getMessage()}";
    echo "Status Code: {$e->getStatusCode()}";
    
    if ($e->getErrorData()) {
        echo "Error Details: " . json_encode($e->getErrorData());
    }
}
```

## Advanced Usage

### Custom Configuration

```php
$client = new DvidsClient(
    baseUrl: 'https://custom-api.example.com',
    accessToken: 'token',
    defaultHeaders: ['X-Custom-Header' => 'value'],
    timeout: 60,
    verifySSL: false
);
```

### Direct API Client Access

```php
$apiClient = $client->getApiClient();
$response = $apiClient->get('/author', ['name' => 'John']);
```

### Working with Models

```php
// Create models programmatically
$author = new Author(
    id: 'author-123',
    name: 'John Smith',
    visionId: 'AB123',
    jobGrade: new JobGrade(
        name: 'Captain',
        associatedPressName: 'Capt.',
        abbreviation: 'CPT',
        branch: Branch::ARMY
    )
);

// Convert to array for API requests
$data = $author->toArray();

// Parse from API responses
$author = Author::fromArray($apiResponse['data']);
```

## File Structure

```
src/
├── DvidsClient.php              # Main client class
├── DvidsApiClient.php           # Low-level HTTP client
├── Exception/                   # Exception classes
│   ├── ApiException.php
│   ├── BadRequestException.php
│   ├── UnauthorizedException.php
│   ├── AuthenticationException.php
│   ├── NotFoundException.php
│   └── ConflictException.php
├── Model/                       # Data model classes
│   ├── Author.php
│   ├── AuthorReference.php
│   ├── Batch.php
│   ├── BatchUpload.php
│   ├── BatchUploadReference.php
│   ├── Branch.php
│   ├── Graphic.php
│   ├── GraphicCategoryReference.php
│   ├── GraphicStatus.php
│   ├── JobGrade.php
│   ├── Photo.php
│   ├── PhotoStatus.php
│   ├── Publication.php
│   ├── PublicationIssue.php
│   ├── PublicationIssueStatus.php
│   ├── PublicationReference.php
│   ├── ServiceUnitReference.php
│   └── ThemeReference.php
└── Resource/                    # Resource-specific clients
    ├── AuthorClient.php
    ├── BatchClient.php
    ├── GraphicClient.php
    ├── PhotoClient.php
    └── PublicationClient.php
```

## Development

### Running Tests

```bash
composer test
```

### Code Analysis

```bash
composer analyze
```

### Code Formatting

```bash
composer cs-fix
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests for new functionality
5. Run the test suite
6. Submit a pull request

## License

This project is licensed under the MIT License.

## Related Documentation

- [DVIDS API Documentation](https://api.dvidshub.net/docs/)
- [OAuth2 Authentication Guide](https://api.dvidshub.net/docs/authentication)
- [DoD Visual Information Style Guide](https://www.dimoc.mil/)

## Support

For issues related to the DVIDS API itself, please contact [dvidsservicedesk@dvidshub.net](mailto:dvidsservicedesk@dvidshub.net). For issues with this PHP client library, please open an issue on the repository.