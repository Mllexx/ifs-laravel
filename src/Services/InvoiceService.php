<?php

namespace Mllexx\IFS\Services;

use Mllexx\IFS\DTO\Invoice;
use Mllexx\IFS\Exceptions\IFSException;
use Mllexx\IFS\Http\IFSClient;

class InvoiceService
{
    /**
     * The HTTP client instance
     *
     * @var IFSClient
     */
    protected IFSClient $client;

    /**
     * The base endpoint for invoice operations
     *
     * @var array
     */
    protected array $endpoints = [
        'Instant'=>[
            'head'=>'/InstantInvoiceHandling.svc/InstantInvoiceSet',
            'line'=>"/InstantInvoiceHandling.svc/InstantInvoiceSet(Company='<company>',InvoiceId=<invoice_id>)/InvoiceItemArray",
        ],
        'Customer'=>[
            'head'=>'/CustomerOrderInvoiceHandling.svc/CustomerOrderInvHeadSet',
            'line'=>'/CustomerOrderInvoiceHandling.svc/CustomerOrderInvLineSet',
        ]
    ];

    protected array $requiredFields = [
        'Instant'=>[
            'Company'=>'string',
            //'InvoiceId'=>'number',
            'Identity'=>'string',
            'PartyType'=>'enum["Company" "Customer" "Supplier" "Person" "Manufacturer" "Owner" "ForwardingAgent" "Employee"]',
            'InvoiceDate'=>'string',
            'DueDate'=>'string',
            'Creator'=>'string',
            'CurrRate'=>'number',
            'InvoiceType'=>'string',
            'PayTermId'=>'string',
            'CreationDate'=>'string',
            'Currency'=>'string',
            'Sent'=>'string',
            'MultiCompanyInvoice'=>'string',
            'PayTermBaseDate'=>'string',
            'AdvInv'=>'boolean',
            'ProposalExist'=>'string',
            'PostPrelTaxWith'=>'boolean',
            'PrepayBasedInv'=>'boolean',
            'UseProjAddressForTax'=>'boolean',
            'SiiProposal'=>'boolean',
            'OperationalKey'=>'boolean',
            'OutInvVouDateBase'=>'string',
            'OutInvCurrRateBase'=>'string',
            'TaxSellCurrRateBase'=>'string',
            'AboveTaxControlLimit'=>'string',
            'EinvoiceSent'=>'boolean',
            'UseDeliveryInvAddress'=>'boolean',
            'ExcludePostingAuth'=>'boolean',
            'CurrDifferenceInvoice'=>'boolean',
            'DigitalInvoice'=>'boolean',
            'TaxAdjustmentInvoice'=>'boolean',
            'Collect'=>'string',
            'Cash'=>'string',
            'IntAllowed'=>'string',
            'AffBaseLedgPost'=>'string',
            'TaxCurrRate'=>'number',
        ],
        'Customer'=>[

        ]
    ];

    protected string $type = 'Customer';
    /**
     * Create a new InvoiceService instance
     *
     * @param IFSClient $client
     */
    public function __construct(IFSClient $client, $type = 'Customer')
    {
        $this->client = $client;
        $this->type = $type;
        $this->endpoint = $this->endpoints[$type];
    }

    /**
     * Get an invoice by ID
     *
     * @param string $invoiceId
     * @return Invoice
     * @throws IFSException
     */
    public function find(string $invoiceId): Invoice
    {
        $response = $this->client->get("{".$this->endpoint['head']."}"."/".$invoiceId);

        return $this->client
                    ->getResponseFactory()
                    ->createInvoice($response->getData());
    }

    /**
     * List all invoices with optional filters
     *
     * @param array $filters
     * @return array
     * @throws IFSException
     */
    public function list(array $filters = [],$batchSize=15): array
    {
        $filters['$top']=$batchSize;
        $response = $this->client->get($this->endpoint['head'].'?$top='.$batchSize, $filters);

        return $this->client
                    ->getResponseFactory()
                    ->createInvoiceCollection($response->getData() ?? []);
    }

    /**
     * Create a new invoice
     *
     * @param array $data
     * @return Invoice
     * @throws IFSException
     */
    public function create(mixed $data): Invoice
    {
        $validationResponse = $this->validatePostData($data);
        $response = $this->client->post($this->endpoint['head'], $data);
        
        return $this->client
                    ->getResponseFactory()
                    ->createInvoice($response->getData());
    }

    /**
     * Update an existing invoice
     *
     * @param string $invoiceId
     * @param array $data
     * @return Invoice
     * @throws IFSException
     */
    public function update(string $invoiceId, array $data): Invoice
    {
        $response = $this->client->put("{$this->endpoint['head']}/{$invoiceId}", $data);

        return $this->client
                    ->getResponseFactory()
                    ->createInvoice($response->getData());
    }

    /**
     * Delete an invoice
     *
     * @param string $invoiceId
     * @return bool
     * @throws IFSException
     */
    public function delete(string $invoiceId): bool
    {
        $response = $this->client->delete("{$this->endpoint['head']}/{$invoiceId}");

        return $response->isSuccessful();
    }

    /**
     * Get invoices for a specific customer
     *
     * @param string $customerId
     * @param array $filters
     * @return array
     * @throws IFSException
     */
    public function getByCustomer(string $customerId, array $filters = []): array
    {
        $response = $this->client->get("customers/{$customerId}/invoices", $filters);

        return $this->client->getResponseFactory()
            ->createInvoiceCollection($response->getData()['data'] ?? []);
    }

    /**
     * Send an invoice to the customer
     *
     * @param string $invoiceId
     * @param array $options
     * @return bool
     * @throws IFSException
     */
    public function send(string $invoiceId, array $options = []): bool
    {
        $response = $this->client->post(
            "{$this->endpoint['head']}/{$invoiceId}/send",
            $options
        );

        return $response->isSuccessful();
    }

    /**
     * Mark an invoice as paid
     *
     * @param string $invoiceId
     * @param array $paymentData
     * @return bool
     * @throws IFSException
     */
    public function markAsPaid(string $invoiceId, array $paymentData = []): bool
    {
        $response = $this->client->post(
            "{$this->endpoint['head']}/{$invoiceId}/pay",
            $paymentData
        );

        return $response->isSuccessful();
    }

    /**
     * Download the PDF version of an invoice
     *
     * @param string $invoiceId
     * @return string|null
     * @throws IFSException
     */
    public function downloadPdf(string $invoiceId): ?string
    {
        $response = $this->client->get(
            "{$this->endpoint['head']}/{$invoiceId}/download",
            ['accept' => 'application/pdf']
        );

        return $response->getData();
    }

    /**
     * Get the pagination metadata from the last response
     *
     * @return array
     */
    public function getPagination(): array
    {
        return $this->client->getResponseFactory()
            ->extractPaginationData($this->client->getLastResponseData() ?? []);
    }


    protected function validatePostData(array $data): array
    {
        //check if data has required fields
        foreach ($this->requiredFields[$this->type] as $name => $type) {
            if (!isset($data[$name])) {
                throw new IFSException("Invoice creation-missing required field: {$name}");
            }
        }
        return $data;
    }

    protected function validateUpdateData(array $data): array
    {
        //check if data has required fields
        foreach ($this->requiredUpdateFields as $name => $type) {
            if (!isset($data[$name])) {
                throw new IFSException("Invoice update-missing required field: {$name}");
            }
        }
        return $data;
    }
}
