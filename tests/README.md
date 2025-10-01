# DVIDS API SDK - PHPUnit Tests

This directory contains comprehensive PHPUnit tests for the DVIDS API SDK, demonstrating proper testing practices with mocked HTTP responses using Guzzle's MockHandler.

## Test Structure

### `DvidsClientTest.php`
Core functionality tests covering:
- Basic API operations (batch creation, uploads, author searches)
- VIRIN generation (both service-unit and author types)
- Error handling (404, 401, network errors)
- Request verification and parameter validation
- Multiple sequential API calls

### `PhotoWorkflowTest.php`
Advanced workflow tests covering:
- Complete photo creation workflows with service-unit VIRINs
- Complete photo creation workflows with author VIRINs
- File upload simulation with mocked S3 responses
- Error handling within multi-step workflows
- Request sequence validation

## Key Testing Features

### 1. HTTP Request Mocking
Uses Guzzle's `MockHandler` to simulate API responses without making real network calls:

```php
$mockHandler = new MockHandler([$response1, $response2, ...]);
$handlerStack = HandlerStack::create($mockHandler);
$mockGuzzleClient = new Client(['handler' => $handlerStack]);
$client = DvidsClient::createWithHttpClient($mockGuzzleClient);
```

### 2. Request History Tracking
All tests track outgoing HTTP requests to verify:
- Correct HTTP methods (GET, POST, PUT, etc.)
- Proper endpoints and paths
- Request body content and structure
- Query parameters
- Headers

```php
$handlerStack->push(Middleware::history($this->requestHistory));
// Later verify:
$this->assertEquals('POST', $this->requestHistory[0]['request']->getMethod());
```

### 3. End-to-End Workflow Testing
Tests complete workflows involving multiple API calls:
- Batch creation → Upload creation → File upload → VIRIN generation → Photo creation
- Proper error propagation when intermediate steps fail
- Verification of complete request sequences

### 4. Error Simulation
Tests various error conditions:
- HTTP errors (400, 401, 404, 500)
- Network exceptions
- API-specific error responses
- Error handling at different workflow stages

## Running Tests

### Prerequisites
Make sure you have PHPUnit and all dependencies installed:

```bash
composer install
```

### Run All Tests
```bash
./vendor/bin/phpunit
```

### Run Specific Test Class
```bash
./vendor/bin/phpunit tests/DvidsClientTest.php
./vendor/bin/phpunit tests/PhotoWorkflowTest.php
```

### Run with Coverage Report
```bash
./vendor/bin/phpunit --coverage-html coverage-html
```

### Run Specific Test Method
```bash
./vendor/bin/phpunit --filter testCreateBatch
./vendor/bin/phpunit --filter testCompletePhotoWorkflowWithServiceUnitVirin
```

## Test Configuration

The `phpunit.xml` configuration file includes:
- Test discovery in the `tests/` directory
- Code coverage reporting
- JUnit XML output for CI/CD integration
- Strict testing mode for better quality

## Mock Response Patterns

### Successful API Response
```php
new Response(201, ['Content-Type' => 'application/vnd.api+json'], json_encode([
    'data' => [
        'id' => 'resource-123',
        'type' => 'resource-type',
        'attributes' => [...],
        'relationships' => [...]
    ]
]))
```

### Error Response
```php
new Response(404, ['Content-Type' => 'application/vnd.api+json'], json_encode([
    'errors' => [
        [
            'title' => 'Not Found',
            'detail' => 'The requested resource was not found.',
            'status' => '404'
        ]
    ]
]))
```

### Network Exception
```php
new RequestException('Connection timeout', new Request('GET', '/test'))
```

## Testing Best Practices Demonstrated

1. **Arrange-Act-Assert Pattern**: All tests follow clear AAA structure
2. **Mock External Dependencies**: No real network calls during testing
3. **Comprehensive Assertions**: Verify both return values and side effects
4. **Error Path Testing**: Test both success and failure scenarios
5. **Request Verification**: Ensure proper API requests are being made
6. **Workflow Testing**: Test complete multi-step operations
7. **Helper Methods**: Reusable test utilities for common operations

## Integration with CI/CD

These tests are designed to run in automated environments:
- No external dependencies or network calls
- Fast execution due to mocked responses
- Deterministic results
- JUnit XML output for CI integration
- Coverage reports for quality metrics

## Extending Tests

To add new tests:

1. Create mock responses matching the API specification
2. Use the `createMockedClient()` helper method
3. Execute the SDK method under test
4. Verify both return values and HTTP requests made
5. Test both success and error scenarios

Example:
```php
public function testNewFeature(): void
{
    // Arrange
    $mockResponse = new Response(200, [], json_encode(['data' => [...]]));
    $client = $this->createMockedClient([$mockResponse]);
    
    // Act
    $result = $client->newFeature()->doSomething();
    
    // Assert
    $this->assertEquals('expected', $result->property);
    $this->assertEquals('POST', $this->requestHistory[0]['request']->getMethod());
}
```