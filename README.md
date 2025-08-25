# IFS PHP Client

A modern PHP client for interacting with the IFS ERP system's API. This package provides a clean, object-oriented interface for managing customers, invoices, and proforma invoices in IFS.

## Features

- **Simple API**: Clean, fluent interface for interacting with IFS
- **Type Safety**: Strongly-typed DTOs for all API resources
- **Error Handling**: Comprehensive exception hierarchy for robust error handling
- **Pagination**: Built-in support for paginated results
- **PSR-7/18 Compatible**: Built on Guzzle HTTP client

## Installation

You can install the package via Composer:

```bash
composer require mllexx/ifs
```

## Configuration

Before using the IFS client, you need to configure it with your API credentials:

```php
use Mllexx\IFS\IFS;

$ifs = new IFS([
    'base_uri' => 'https://your-ifs-instance.com/api/v1',
    'api_key' => 'your-api-key',
    'timeout' => 30, // Optional, defaults to 30 seconds
]);
```

## Usage

### Customers

#### Create a new customer

```php
try {
    $customer = $ifs->customers()->create([
        'name' => 'Acme Inc.',
        'email' => 'billing@acme.com',
        'phone' => '+1234567890',
        'address' => '123 Business St, City, Country',
        'tax_id' => 'TAX123456',
        'currency' => 'USD',
    ]);
    
    echo "Created customer ID: " . $customer->id;
} catch (\Mllexx\IFS\Exceptions\IFSException $e) {
    echo "Error creating customer: " . $e->getMessage();
}
```

#### Get a customer

```php
try {
    $customer = $ifs->customers()->find('CUST123');
    
    echo "Customer: " . $customer->name;
    echo "Email: " . $customer->email;
    
    // Get all invoices for this customer
    $invoices = $ifs->invoices()->getByCustomer($customer->id);
    
    foreach ($invoices as $invoice) {
        echo "Invoice #" . $invoice->number . ": " . $invoice->total_amount . " " . $invoice->currency;
    }
} catch (\Mllexx\IFS\Exceptions\NotFoundException $e) {
    echo "Customer not found";
} catch (\Mllexx\IFS\Exceptions\IFSException $e) {
    echo "Error: " . $e->getMessage();
}
```

### Invoices

#### Create an invoice

```php
try {
    $invoice = $ifs->invoices()->create([
        'customer_id' => 'CUST123',
        'date' => new DateTime(),
        'due_date' => (new DateTime())->modify('+30 days'),
        'currency' => 'USD',
        'items' => [
            [
                'description' => 'Web Development Services',
                'quantity' => 10,
                'unit_price' => 100.00,
                'tax_rate' => 20.0,
            ],
            [
                'description' => 'Consulting',
                'quantity' => 5,
                'unit_price' => 150.00,
                'tax_rate' => 20.0,
            ],
        ],
        'notes' => 'Thank you for your business!',
    ]);
    
    echo "Created invoice #" . $invoice->number;
} catch (\Mllexx\IFS\Exceptions\ValidationException $e) {
    echo "Validation errors: " . print_r($e->getErrors(), true);
} catch (\Mllexx\IFS\Exceptions\IFSException $e) {
    echo "Error creating invoice: " . $e->getMessage();
}
```

#### Send an invoice

```php
try {
    $success = $ifs->invoices()->send('INV-2023-001', [
        'send_email' => true,
        'email_recipients' => ['billing@client.com', 'accounting@client.com'],
        'email_subject' => 'Your Invoice #{number}',
        'email_message' => 'Please find attached your invoice for {amount}.'
    ]);
    
    if ($success) {
        echo "Invoice sent successfully";
    }
} catch (\Mllexx\IFS\Exceptions\IFSException $e) {
    echo "Error sending invoice: " . $e->getMessage();
}
```

### Proforma Invoices

#### Create a proforma invoice

```php
try {
    $proforma = $ifs->proformaInvoices()->create([
        'customer_id' => 'CUST123',
        'date' => new DateTime(),
        'valid_until' => (new DateTime())->modify('+7 days'),
        'currency' => 'USD',
        'items' => [
            [
                'description' => 'Web Development Services',
                'quantity' => 10,
                'unit_price' => 100.00,
                'tax_rate' => 20.0,
            ]
        ],
        'notes' => 'This is a proforma invoice',
    ]);
    
    echo "Created proforma invoice #" . $proforma->number;
} catch (\Mllexx\IFS\Exceptions\IFSException $e) {
    echo "Error: " . $e->getMessage();
}
```

#### Convert proforma invoice to regular invoice

```php
try {
    $invoiceId = $ifs->proformaInvoices()->convertToInvoice('PROF-2023-001');
    echo "Created regular invoice with ID: " . $invoiceId;
} catch (\Mllexx\IFS\Exceptions\IFSException $e) {
    echo "Error converting proforma invoice: " . $e->getMessage();
}
```

## Error Handling

The package throws specific exceptions for different types of errors:

- `IFSException`: Base exception for all IFS API errors
- `AuthenticationException`: Authentication failed
- `AuthorizationException`: Insufficient permissions
- `NotFoundException`: Requested resource not found
- `ValidationException`: Invalid request data
- `RateLimitExceededException`: API rate limit exceeded
- `ServerException`: Server error (5xx)

## Testing

### Unit Tests

Run the test suite:

```bash
composer test
```

### Integration Tests

The package includes integration tests that verify the client works with a real HTTP client. These tests use a mock handler to simulate API responses without making actual HTTP requests.

#### Example Integration Test

```php
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Mllexx\IFS\Http\IFSClient;
use Mllexx\IFS\IFS;

// Create a mock handler and client
$mockHandler = new MockHandler();
$handlerStack = HandlerStack::create($mockHandler);

$client = new IFSClient([
    'base_uri' => 'https://api.ifs.test/v1',
    'handler' => $handlerStack,
    'api_key' => 'test-api-key',
]);

$ifs = new IFS($client);

// Queue a mock response for customer creation
$mockHandler->append(new Response(201, [], json_encode([
    'data' => [
        'id' => 'CUST123',
        'name' => 'Test Customer',
        'email' => 'test@example.com',
        // ... other customer fields
    ],
    'message' => 'Customer created successfully',
])));

// Now the API call will use the mocked response
$customer = $ifs->customers()->create([
    'name' => 'Test Customer',
    'email' => 'test@example.com',
    // ... other customer data
]);
```

#### Running Integration Tests

To run the integration tests, use PHPUnit:

```bash
./vendor/bin/phpunit --testsuite Feature
```

### Test Coverage

The test suite includes:

- **Unit Tests**: Test individual components in isolation
- **Feature Tests**: Test the integration between components
- **Error Handling**: Verify proper exception handling
- **Edge Cases**: Test boundary conditions and error scenarios

To generate a code coverage report (requires Xdebug or PCOV):

```bash
composer test-coverage
```

This will generate an HTML coverage report in the `coverage` directory.

## Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
