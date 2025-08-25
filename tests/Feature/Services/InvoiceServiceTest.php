<?php

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Mllexx\IFS\DTO\ApiResponse;
use Mllexx\IFS\DTO\Invoice;
use Mllexx\IFS\Exceptions\IFSException;
use Mllexx\IFS\Http\IFSClient;
use Mllexx\IFS\Services\InvoiceService;

beforeEach(function () {
    // Mock handler for Guzzle
    $this->mockHandler = new MockHandler();
    
    // Create a handler stack with our mock handler
    $handlerStack = HandlerStack::create($this->mockHandler);
    
    // Create a Guzzle client with the mock handler
    $this->client = new IFSClient([
        'base_uri' => 'https://ifs-cld-cfg-mdlw.bulkstream.com/main/ifsapplications/projection/v1',
        'client_id' => 'test_client',
        'client_secret' => 'test_secret',
        'token_endpoint' => 'https://example.com/token',
        'timeout' => 5,
        'handler' => $handlerStack,
    ]);

    // Create InvoiceService instance with mocked client
    $this->invoiceService = new InvoiceService($this->client, 'Customer');
});

test('it can be instantiated', function () {
    expect($this->invoiceService)->toBeInstanceOf(InvoiceService::class);
});

test('it can find an invoice by id', function () {
    // Mock token response
    $this->mockHandler->append(new Response(
        200,
        ['Content-Type' => 'application/json'],
        json_encode([
            'InvoiceId' => 'INV123',
            'Identity' => 'GB-BAK03K',
            'InvoiceDate' => '2025-03-03',
            // Add other required fields
        ])
    );

    $invoice = $this->invoiceService->find('INV123');
    
    expect($invoice)->toBeInstanceOf(Invoice::class)
        ->and($invoice->InvoiceId)->toBe('INV123');
});

test('it can list invoices', function () {
    // Mock token response and list response
    $this->mockHandler->append(new Response(
        200,
        ['Content-Type' => 'application/json'],
        json_encode([
            ['InvoiceId' => 'INV123', 'Identity' => 'GB-BAK03K'],
            ['InvoiceId' => 'INV124', 'Identity' => 'GB-BAK04L']
        ])
    );

    $invoices = $this->invoiceService->list();
    
    expect($invoices)->toBeArray()
        ->and($invoices)->toHaveCount(2)
        ->and($invoices[0])->toBeInstanceOf(Invoice::class);
});

test('it can create an invoice', function () {
    $invoiceData = [
        'Company' => 'GBHL',
        'Identity' => 'GB-BAK03K',
        'PartyType' => 'Customer',
        'InvoiceDate' => '2025-03-03',
        'DueDate' => '2025-03-03',
        'Creator' => 'API_TEST',
        'CurrRate' => 1,
        'InvoiceType' => 'TEST',
        'PayTermId' => '0',
        'CreationDate' => '2025-03-03T00:00:00Z',
        'Currency' => 'KES',
        'Sent' => 'FALSE',
        'MultiCompanyInvoice' => 'FALSE',
        'PayTermBaseDate' => '2025-03-03',
        'AdvInv' => false,
        'ProposalExist' => 'FALSE',
        'PostPrelTaxWith' => true,
        'PrepayBasedInv' => false,
        'UseProjAddressForTax' => false,
        'SiiProposal' => false,
        'OperationalKey' => 'NoValue',
        'OutInvVouDateBase' => 'InvoiceDate',
        'OutInvCurrRateBase' => 'InvoiceDate',
        'TaxSellCurrRateBase' => 'InvoiceDate',
        'AboveTaxControlLimit' => false,
        'EinvoiceSent' => false,
        'UseDeliveryInvAddress' => false,
        'ExcludePostingAuth' => false,
        'CurrDifferenceInvoice' => false,
        'DigitalInvoice' => false,
        'TaxAdjustmentInvoice' => false,
        'Collect' => 'FALSE',
        'Cash' => 'FALSE',
        'IntAllowed' => 'TRUE',
        'AffBaseLedgPost' => 'TRUE',
        'TaxCurrRate' => 1,
    ];

    // Mock token response and create response
    $this->mockHandler->append(new Response(
        201,
        ['Content-Type' => 'application/json'],
        json_encode(array_merge($invoiceData, ['InvoiceId' => 'INV123']))
    ));

    $invoice = $this->invoiceService->create($invoiceData);
    
    expect($invoice)->toBeInstanceOf(Invoice::class)
        ->and($invoice->InvoiceId)->toBe('INV123');
});

test('it validates required fields when creating invoice', function () {
    $invalidData = [];
    
    $this->expectException(IFSException::class);
    $this->expectExceptionMessage('Invoice creation-missing required field: Company');
    
    $this->invoiceService->create($invalidData);
});

test('it can update an invoice', function () {
    $updateData = [
        'Identity' => 'UPDATED-123',
        'InvoiceDate' => '2025-04-01',
        // Add other fields that can be updated
    ];

    // Mock token response and update response
    $this->mockHandler->append(new Response(
        200,
        ['Content-Type' => 'application/json'],
        json_encode(array_merge($updateData, ['InvoiceId' => 'INV123']))
    ));

    $invoice = $this->invoiceService->update('INV123', $updateData);
    
    expect($invoice)->toBeInstanceOf(Invoice::class)
        ->and($invoice->Identity)->toBe('UPDATED-123');
});

test('it can delete an invoice', function () {
    // Mock token response and delete response
    $this->mockHandler->append(new Response(204));
    
    $result = $this->invoiceService->delete('INV123');
    
    expect($result)->toBeTrue();
});

test('it can get invoices by customer', function () {
    // Mock token response and customer invoices response
    $this->mockHandler->append(new Response(
        200,
        ['Content-Type' => 'application/json'],
        json_encode([
            ['InvoiceId' => 'INV123', 'Identity' => 'GB-BAK03K'],
            ['InvoiceId' => 'INV124', 'Identity' => 'GB-BAK04L']
        ])
    ));

    $invoices = $this->invoiceService->getByCustomer('CUST123');
    
    expect($invoices)->toBeArray()
        ->and($invoices)->toHaveCount(2)
        ->and($invoices[0])->toBeInstanceOf(Invoice::class);
});

test('it can send an invoice', function () {
    // Mock token response and send invoice response
    $this->mockHandler->append(new Response(200));
    
    $result = $this->invoiceService->send('INV123', ['method' => 'email']);
    
    expect($result)->toBeTrue();
});

test('it can mark an invoice as paid', function () {
    // Mock token response and mark as paid response
    $this->mockHandler->append(new Response(200));
    
    $result = $this->invoiceService->markAsPaid('INV123', [
        'amount' => 1000,
        'payment_date' => '2025-03-03'
    ]);
    
    expect($result)->toBeTrue();
});

test('it can download invoice PDF', function () {
    $pdfContent = '%PDF-1.4...';
    
    // Mock token response and PDF download response
    $this->mockHandler->append(new Response(
        200,
        ['Content-Type' => 'application/pdf'],
        $pdfContent
    ));
    
    $result = $this->invoiceService->downloadPdf('INV123');
    
    expect($result)->toBe($pdfContent);
});
