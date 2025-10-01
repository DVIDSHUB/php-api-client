<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use DvidsApi\DvidsClient;
use DvidsApi\Exception\ApiException;
use DvidsApi\Model\BatchUpload;

/**
 * DVIDS API BatchUpload Workflow Example
 * 
 * This example demonstrates how to work with BatchUpload objects
 * for uploading files to the DVIDS Content Submission API.
 */

echo "=== DVIDS Batch Upload Workflow Example ===\n\n";

try {
    // Create an authenticated client
    $client = new DvidsClient();
    // $client = $client->withAccessToken('your-access-token-here'); // Uncomment for production
    
    echo "Client created\n";
    
    // === METHOD 1: Separate steps (gives you more control) ===
    echo "\n--- Method 1: Separate Steps ---\n";
    
    /*
    // Step 1: Create a batch
    $batch = $client->batches()->createBatch();
    echo "Created batch: " . $batch->id . "\n";
    
    // Step 2: Create a batch upload to get presigned URL
    $batchUpload = $client->batches()->createBatchUpload($batch->id, true);
    echo "Created batch upload: " . $batchUpload->id . "\n";
    echo "Upload method: " . $batchUpload->httpMethod . "\n";
    echo "Uses CDN: " . ($batchUpload->useCdn ? 'Yes' : 'No') . "\n";
    echo "Upload URL: " . substr($batchUpload->uploadUrl, 0, 50) . "...\n";
    
    // Step 3: Upload the file using the BatchUpload object
    $filePath = '/path/to/your/file.jpg';
    $contentType = 'image/jpeg';
    
    $uploadResult = $client->batches()->uploadFile($batchUpload, $filePath, $contentType);
    echo "File uploaded successfully!\n";
    echo "BatchUpload ID: " . $uploadResult['batch_upload']->id . "\n";
    
    // The batch upload ID can now be used in content metadata (photos, graphics, etc.)
    $batchUploadId = $uploadResult['batch_upload']->id;
    */
    
    // === METHOD 2: Combined operation (simpler) ===
    echo "\n--- Method 2: Combined Operation ---\n";
    
    /*
    // Create batch
    $batch = $client->batches()->createBatch();
    echo "Created batch: " . $batch->id . "\n";
    
    // Create batch upload and upload file in one step
    $filePath = '/path/to/your/file.jpg';
    $contentType = 'image/jpeg';
    
    $uploadResult = $client->batches()->createAndUploadFile(
        $batch->id,
        $filePath,
        $contentType,
        true // use CDN
    );
    
    echo "File uploaded successfully!\n";
    echo "BatchUpload ID: " . $uploadResult['batch_upload']->id . "\n";
    
    $batchUploadId = $uploadResult['batch_upload']->id;
    */
    
    // === WORKING WITH DIFFERENT FILE TYPES ===
    echo "\n--- Supported File Types ---\n";
    
    $supportedFiles = [
        'image/jpeg' => ['.jpg', '.jpeg'],
        'image/png' => ['.png'],
        'image/gif' => ['.gif'],
        'application/pdf' => ['.pdf'],
        'application/zip' => ['.zip'],
        'video/mp4' => ['.mp4'],
        'application/octet-stream' => ['.indd', '.psd', '.ai'] // Design files
    ];
    
    foreach ($supportedFiles as $mimeType => $extensions) {
        echo "- {$mimeType}: " . implode(', ', $extensions) . "\n";
    }
    
    // === CREATING CONTENT WITH UPLOADED FILES ===
    echo "\n--- Creating Content with Uploaded Files ---\n";
    
    /*
    // Example: Creating a graphic with uploaded image
    $graphicResult = $client->graphics()->createSimpleGraphic(
        batchId: $batch->id,
        title: 'Sample Graphic Title',
        description: 'Description of the graphic',
        instructions: 'Release instructions',
        createdAt: new DateTime(),
        virin: 'YYMMDD-X-XXXXX-XXX', // Visual Information Record Identification Number
        country: 'US',
        batchUploadId: $batchUploadId, // Use the uploaded file
        categoryId: 'graphic-category-id',
        serviceUnitId: 'service-unit-id',
        tags: ['training', 'army', 'exercise']
    );
    
    echo "Created graphic: " . $graphicResult->id . "\n";
    */
    
    /*
    // Example: Creating a publication issue with uploaded PDF
    $publicationIssue = $client->publications()->createPublicationIssueWithUpload(
        batchId: $batch->id,
        pdfFilePath: '/path/to/publication.pdf',
        description: 'Publication issue description',
        createdAt: new DateTime(),
        publicationId: 'publication-id',
        batchClient: $client->batches()
    );
    
    echo "Created publication issue: " . $publicationIssue['publication_issue']->id . "\n";
    */
    
    // === BATCH UPLOAD OBJECT PROPERTIES ===
    echo "\n--- BatchUpload Object Properties ---\n";
    
    echo "Available properties on BatchUpload objects:\n";
    echo "- id: Unique identifier for the batch upload\n";
    echo "- uploadUrl: Presigned URL for uploading the file\n";
    echo "- httpMethod: HTTP method to use (usually 'PUT')\n";
    echo "- useCdn: Whether CDN acceleration is enabled\n";
    echo "- multipartFormUploadParams: Form parameters for POST uploads (if applicable)\n";
    echo "- batchId: ID of the parent batch\n";
    
    echo "\nUtility methods:\n";
    echo "- isMultipartFormUpload(): Check if it's a POST multipart upload\n";
    echo "- isPutUpload(): Check if it's a simple PUT upload\n";
    
    // === ERROR HANDLING ===
    echo "\n--- Error Handling ---\n";
    
    echo "Common upload errors to handle:\n";
    echo "- File too large: Files over 5GB need multipart upload\n";
    echo "- Network errors: Implement retry logic for reliability\n";
    
    /*
    try {
        $uploadResult = $client->batches()->createAndUploadFile(
            $batch->id,
            $filePath,
            $contentType
        );
        echo "Upload successful!\n";
    } catch (ApiException $e) {
        echo "Upload failed: " . $e->getMessage() . "\n";
        echo "Status code: " . $e->getStatusCode() . "\n";
        
        // Handle specific errors
        if ($e->getStatusCode() === 413) {
            echo "File too large - consider using multipart upload\n";
        } elseif ($e->getStatusCode() === 401) {
            echo "Authentication required\n";
        }
    } catch (\InvalidArgumentException $e) {
        echo "Invalid file or parameters: " . $e->getMessage() . "\n";
    }
    */
    
} catch (ApiException $e) {
    echo "API Error: " . $e->getMessage() . "\n";
    echo "Status Code: " . $e->getStatusCode() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}