<?php

namespace Mllexx\IFS\Services;

use Mllexx\IFS\DTO\ProformaInvoice;
use Mllexx\IFS\Exceptions\IFSException;
use Mllexx\IFS\Http\IFSClient;

class ProformaInvoiceService
{
    /**
     * The HTTP client instance
     *
     * @var IFSClient
     */
    protected IFSClient $client;

    /**
     * The base endpoint for proforma invoice operations
     *
     * @var string
     */
    protected string $endpoint = 'proforma-invoices';

    /**
     * Create a new ProformaInvoiceService instance
     *
     * @param IFSClient $client
     */
    public function __construct(IFSClient $client)
    {
        $this->client = $client;
    }

    /**
     * Get a proforma invoice by ID
     *
     * @param string $invoiceId
     * @return ProformaInvoice
     * @throws IFSException
     */
    public function find(string $invoiceId): ProformaInvoice
    {
        $response = $this->client->get("{$this->endpoint}/{$invoiceId}");

        return $this->client->getResponseFactory()
            ->createProformaInvoice($response->getData());
    }

    /**
     * List all proforma invoices with optional filters
     *
     * @param array $filters
     * @return array
     * @throws IFSException
     */
    public function list(array $filters = []): array
    {
        $response = $this->client->get($this->endpoint, $filters);

        return $this->client->getResponseFactory()
            ->createProformaInvoiceCollection($response->getData()['data'] ?? []);
    }

    /**
     * Create a new proforma invoice
     *
     * @param array $data
     * @return ProformaInvoice
     * @throws IFSException
     */
    public function create(array $data): ProformaInvoice
    {
        $response = $this->client->post($this->endpoint, $data);

        return $this->client->getResponseFactory()
            ->createProformaInvoice($response->getData());
    }

    /**
     * Update an existing proforma invoice
     *
     * @param string $invoiceId
     * @param array $data
     * @return ProformaInvoice
     * @throws IFSException
     */
    public function update(string $invoiceId, array $data): ProformaInvoice
    {
        $response = $this->client->put("{$this->endpoint}/{$invoiceId}", $data);

        return $this->client->getResponseFactory()
            ->createProformaInvoice($response->getData());
    }

    /**
     * Delete a proforma invoice
     *
     * @param string $invoiceId
     * @return bool
     * @throws IFSException
     */
    public function delete(string $invoiceId): bool
    {
        $response = $this->client->delete("{$this->endpoint}/{$invoiceId}");

        return $response->isSuccessful();
    }

    /**
     * Convert a proforma invoice to a regular invoice
     *
     * @param string $invoiceId
     * @param array $options
     * @return string The ID of the created invoice
     * @throws IFSException
     */
    public function convertToInvoice(string $invoiceId, array $options = []): string
    {
        $response = $this->client->post(
            "{$this->endpoint}/{$invoiceId}/convert",
            $options
        );

        return $response->getData()['invoice_id'] ?? '';
    }

    /**
     * Send a proforma invoice to the customer
     *
     * @param string $invoiceId
     * @param array $options
     * @return bool
     * @throws IFSException
     */
    public function send(string $invoiceId, array $options = []): bool
    {
        $response = $this->client->post(
            "{$this->endpoint}/{$invoiceId}/send",
            $options
        );

        return $response->isSuccessful();
    }

    /**
     * Download the PDF version of a proforma invoice
     *
     * @param string $invoiceId
     * @return string|null
     * @throws IFSException
     */
    public function downloadPdf(string $invoiceId): ?string
    {
        $response = $this->client->get(
            "{$this->endpoint}/{$invoiceId}/download",
            ['accept' => 'application/pdf']
        );

        return $response->getData();
    }

    /**
     * Get proforma invoices for a specific customer
     *
     * @param string $customerId
     * @param array $filters
     * @return array
     * @throws IFSException
     */
    public function getByCustomer(string $customerId, array $filters = []): array
    {
        $response = $this->client->get("customers/{$customerId}/proforma-invoices", $filters);

        return $this->client->getResponseFactory()
            ->createProformaInvoiceCollection($response->getData()['data'] ?? []);
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
}
