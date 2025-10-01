# DVIDS Photo Creation Guide

This guide demonstrates how to create photos in the DVIDS Content Submission API using the PHP SDK with proper VIRIN generation and relationship handling.

## Overview

The PhotoClient provides comprehensive support for creating photos with:
- **Automatic VIRIN Generation**: Generate VIRINs from service units or authors
- **BatchUpload Integration**: Proper file upload with presigned URLs
- **Complete Relationships**: Service units, authors, themes, and batch uploads
- **Full Metadata Support**: All photo attributes including location, operation details, etc.
- **Dual VIRIN Workflows**: Service unit VIRINs for official activities, author VIRINs for individual work

## Quick Start

```php
use DvidsApi\DvidsClient;

$client = new DvidsClient();
$client = $client->withAccessToken('your-access-token');

// Create batch
$batch = $client->batches()->createBatch();

// SERVICE UNIT WORKFLOW (Official unit activities)
$result = $client->photos()->createCompletePhotoWorkflowWithServiceUnitVirin(
    batchId: $batch->id,
    imageFilePath: '/path/to/photo.jpg',
    contentType: 'image/jpeg',
    title: 'Military Training Exercise',
    description: 'Soldiers participate in joint training exercise.',
    instructions: 'Cleared for public release. Distribution unlimited.',
    createdAt: new DateTimeImmutableImmutable('2024-10-01 14:30:00'),
    country: 'US',
    serviceUnitId: 'service-unit-123', // Service unit generates VIRIN
    tags: ['training', 'military', 'exercise'],
    batchClient: $client->batches(),
    authorIds: ['author-123', 'author-456']
);

// AUTHOR WORKFLOW (Individual photographer work)
$result = $client->photos()->createCompletePhotoWorkflowWithAuthorVirin(
    batchId: $batch->id,
    imageFilePath: '/path/to/photo.jpg',
    contentType: 'image/jpeg',
    title: 'Combat Photography Documentation',
    description: 'Individual photographer documentation.',
    instructions: 'Cleared for public release. Distribution unlimited.',
    createdAt: new DateTimeImmutableImmutable('2024-10-01 16:30:00'),
    country: 'US',
    authorId: 'photographer-123', // Author generates VIRIN
    tags: ['photography', 'documentation'],
    batchClient: $client->batches(),
    serviceUnitId: 'unit-456'
);

$photo = $result['photo'];
echo "Created photo: {$photo->id} with VIRIN: {$photo->virin}";

// Submit for approval
$client->batches()->closeBatch($batch->id);
```

## When to Use Each VIRIN Type

### Service Unit VIRIN
Use `createCompletePhotoWorkflow()` when:
- Creating photos of official unit activities
- Documenting training exercises conducted by the unit
- Capturing ceremonial events representing the unit
- Any photo that officially represents the military unit

**VIRIN Format**: `YYMMDD-[BRANCH]-[UNIT_ID]-XXXX`  
**Example**: `241001-A-AB123-1001` (Army unit AB123)

### Author VIRIN
Use `createCompletePhotoWorkflowWithAuthorVirin()` when:
- Individual photographer assignments
- Personal documentation projects
- Freelance or contracted photography work
- When no specific unit is being officially represented

**VIRIN Format**: `YYMMDD-P-[AUTHOR_ID]-XXXX`  
**Example**: `241001-P-JD123-1001` (Photographer JD123)

## Photo Creation Methods

### 1. Complete Workflow (Recommended)

```php
$result = $client->photos()->createCompletePhotoWorkflow(
    batchId: $batch->id,
    imageFilePath: '/path/to/photo.jpg',
    contentType: 'image/jpeg',
    title: 'Photo Title',
    description: 'Photo description',
    instructions: 'Release instructions',
    createdAt: new DateTimeImmutableImmutable(),
    country: 'US',
    serviceUnitId: 'service-unit-id',
    tags: ['tag1', 'tag2'],
    batchClient: $client->batches(),
    subdiv: 'VA',
    city: 'Fort Liberty',
    captionWriter: 'Sgt. John Smith',
    jobIdentifier: 'JOB-2024-001',
    operationName: 'Operation Ready Eagle',
    authorIds: ['author-123'],
    themeIds: ['theme-training']
);

// Returns: DvidsApi\Model\Photo
```

### 2. Step-by-Step Approach

```php
// Step 1: Upload image file
$uploadResult = $client->batches()->createAndUploadFile(
    $batch->id,
    '/path/to/photo.jpg',
    'image/jpeg'
);
$batchUploadId = $uploadResult['batch_upload']->id;

// Step 2: Generate VIRIN
$virinResult = $client->photos()->createServiceUnitVirin(
    'service-unit-123',
    new DateTimeImmutable('2024-10-01')
);
$virin = $virinResult['data']['attributes']['virin'];

// Step 3: Create photo
$photo = $client->photos()->createSimplePhoto(
    batchId: $batch->id,
    title: 'Photo Title',
    description: 'Photo description',
    instructions: 'Release instructions',
    createdAt: new DateTimeImmutable(),
    virin: $virin,
    country: 'US',
    batchUploadId: $batchUploadId,
    serviceUnitId: 'service-unit-123',
    tags: ['training']
);
```

### 3. Auto-Generated VIRIN

```php
// Requires pre-uploaded file
$photo = $client->photos()->createPhotoWithServiceUnitGeneratedVirin(
    batchId: $batch->id,
    title: 'Photo Title',
    description: 'Photo description',
    instructions: 'Release instructions',
    createdAt: new DateTimeImmutable(),
    country: 'US',
    batchUploadId: $batchUploadId,
    serviceUnitId: 'service-unit-123',
    tags: ['training']
);
```

## VIRIN Generation

### Service Unit VIRIN Generation

The SDK automatically generates VIRINs from service units:

```php
$virinResult = $client->serviceUnits()->createVirin(
    serviceUnitId: 'service-unit-123',
    date: new DateTimeImmutable('2024-10-01')
);

$virin = $virinResult['data']['attributes']['virin'];
// Example result: "241001-A-AB123-1001"
```

### Author VIRIN Generation

The SDK can also generate VIRINs from individual authors:

```php
$virinResult = $client->authors()->generateVirin(
    authorId: 'photographer-123',
    date: new DateTimeImmutable('2024-10-01')
);

$virin = $virinResult['data']['attributes']['virin'];
// Example result: "241001-P-JD123-1001"
```

### VIRIN Format
- `YYMMDD`: Date (241001 = Oct 1, 2024)
- `X`: Service branch code (A = Army, N = Navy, etc.)
- `XXXXX`: Unit identifier (from service unit)
- `XXXX`: Sequential number (automatically assigned)

## Required Relationships

### Batch Upload
Links the photo to the uploaded image file:
```php
'batch_upload' => [
    'data' => [
        'id' => $batchUploadId,
        'type' => 'batch-upload'
    ]
]
```

### Service Unit
Links the photo to the military unit:
```php
'service_unit' => [
    'data' => [
        'id' => $serviceUnitId,
        'type' => 'service-unit'
    ]
]
```

## Optional Relationships

### Authors
Credits photographers and contributors:
```php
'authors' => [
    'data' => [
        ['id' => 'author-123', 'type' => 'author'],
        ['id' => 'author-456', 'type' => 'author']
    ]
]
```

### Themes
Associates photos with content themes:
```php
'themes' => [
    'data' => [
        ['id' => 'theme-training', 'type' => 'theme'],
        ['id' => 'theme-readiness', 'type' => 'theme']
    ]
]
```

## Photo Attributes

### Required Attributes
- `title`: Photo headline/title
- `description`: Detailed photo description
- `instructions`: Release authority and review instructions
- `created_at`: When the photo was taken (ISO 8601 format)
- `virin`: Visual Information Record Identification Number
- `country`: ISO-3166-2 country code
- `tags`: Array of content tags

### Optional Attributes
- `subdiv`: State/province code
- `city`: City name
- `caption_writer`: Name of person who wrote the caption
- `job_identifier`: Job/assignment identifier
- `operation_name`: Military operation name

## Complete API Request Structure

```php
[
    'data' => [
        'type' => 'photo',
        'attributes' => [
            'title' => 'Military Training Exercise',
            'description' => 'Soldiers participate in training.',
            'instructions' => 'Cleared for public release.',
            'created_at' => '2024-10-01T14:30:00+00:00',
            'virin' => '241001-A-AB123-001',
            'country' => 'US',
            'subdiv' => 'VA',
            'city' => 'Fort Liberty',
            'caption_writer' => 'Sgt. John Smith',
            'job_identifier' => 'TRAINING-2024-001',
            'operation_name' => 'Operation Ready Eagle',
            'tags' => ['training', 'military', 'exercise']
        ],
        'relationships' => [
            'batch_upload' => [
                'data' => ['id' => 'batch-upload-456', 'type' => 'batch-upload']
            ],
            'service_unit' => [
                'data' => ['id' => 'service-unit-123', 'type' => 'service-unit']
            ],
            'authors' => [
                'data' => [
                    ['id' => 'author-123', 'type' => 'author'],
                    ['id' => 'author-456', 'type' => 'author']
                ]
            ],
            'themes' => [
                'data' => [
                    ['id' => 'theme-training', 'type' => 'theme']
                ]
            ]
        ]
    ]
]
```

## Error Handling

```php
try {
    $result = $client->photos()->createCompletePhotoWorkflow(...);
    echo "Photo created: {$result['photo']->id}";
} catch (ApiException $e) {
    echo "API Error: {$e->getMessage()}";
    echo "Status: {$e->getStatusCode()}";
} catch (RuntimeException $e) {
    echo "VIRIN generation failed: {$e->getMessage()}";
} catch (InvalidArgumentException $e) {
    echo "Invalid photo data: {$e->getMessage()}";
}
```

## Supported Image Formats

- JPEG (`.jpg`, `.jpeg`)

## Best Practices

1. **Use Complete Workflow**: The `createCompletePhotoWorkflow()` method handles all steps automatically
2. **Provide Detailed Descriptions**: Include comprehensive photo descriptions for searchability
3. **Use Appropriate Tags**: Tag photos with relevant keywords for categorization
4. **Include Location Data**: Provide `subdiv` and `city` when available
5. **Credit Authors**: Credit is assigned automatically when the VISION ID in the VIRIN matches an Author on file
6. **Close Batches**: Remember to close batches after adding all content

## PhotoClient Methods

**Complete Workflows:**
- `createCompletePhotoWorkflow()` - End-to-end service unit photo creation
- `createCompletePhotoWorkflowWithAuthorVirin()` - End-to-end author photo creation

**Photo Creation:**
- `createPhotoWithServiceUnitGeneratedVirin()` - Create photo with auto-generated service unit VIRIN
- `createPhotoWithAuthorGeneratedVirin()` - Create photo with auto-generated author VIRIN
- `createSimplePhoto()` - Create photo with provided VIRIN
- `createBatchPhoto()` - Create photo with raw API data

**Photo Management:**
- `getBatchPhoto()` - Retrieve photo from batch
- `updateBatchPhoto()` - Update existing photo
- `deleteBatchPhoto()` - Delete photo from batch
