# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a REST API client library for Dynamic WMS (Warehouse Management System). It provides a PHP interface to interact with the Dynamic WMS v2 API.

## Architecture

The package follows a simple client-adapter pattern:

1. **Main Entry Point**: `DynamicWms` class (src/DynamicWms.php:12)
   - Initializes with config array containing `bearer` token
   - Creates Guzzle HTTP client with base URI: `https://v2.dynamicwms.app/api/v1/`
   - Provides factory methods for API endpoints: `orders()`, `fulfilments()`, `deliveries()`, `returns()`

2. **API Client Base**: `ApiClient` class (src/Api/ApiClient.php:9)
   - Handles HTTP requests (GET, POST, PUT) with bearer token authentication
   - Automatically parses JSON responses
   - All API endpoint classes extend this base class

3. **API Endpoints**: Located in `src/Api/`
   - `Orders`: Handle order creation and retrieval
   - `Fulfilments`: Manage fulfillment operations
   - `Deliveries`: Track deliveries
   - `Returns`: Process returns

## Development Commands

```bash
# Install dependencies
composer install

# Run tests
composer test

# Run tests with coverage
composer test-coverage
```

## Testing

The package uses PHPUnit for testing with Orchestra Testbench for Laravel package testing support. Test files should be placed in the `tests/` directory.

To run a single test:
```bash
vendor/bin/phpunit tests/YourTestFile.php
vendor/bin/phpunit --filter test_method_name
```

## Code Standards

- Follows PSR-2 coding standard
- PHP 7.1+ compatibility (supports PHP 8.0)
- Uses PSR-4 autoloading with namespace `Booni3\DynamicWms`

## Configuration

The package requires a bearer token for authentication:
```php
$config = [
    'bearer' => 'your-api-token',
    'timeout' => 15 // optional, defaults to 15 seconds
];
$client = DynamicWms::make($config);
```

## Key Implementation Details

- All API methods return arrays (parsed JSON responses)
- HTTP errors throw `GuzzleHttp\Exception\BadResponseException`
- JSON parsing errors also throw `BadResponseException`
- The package is designed to be used as a standalone library or within Laravel applications