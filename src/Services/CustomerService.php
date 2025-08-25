<?php

namespace Mllexx\IFS\Services;

use Mllexx\IFS\DTO\Customer;
use Mllexx\IFS\Exceptions\IFSException;
use Mllexx\IFS\Http\IFSClient;

class CustomerService
{
    /**
     * The HTTP client instance
     *
     * @var IFSClient
     */
    protected IFSClient $client;

    /**
     * The base endpoint for customer operations
     *
     * @var string
     */
    protected string $endpoint = '/CustomersHandling.svc/CustomerInfoSet';

    /**
     * Create a new CustomerService instance
     *
     * @param IFSClient $client
     */
    public function __construct(IFSClient $client)
    {
        $this->client = $client;
    }

    /**
     * Get a customer by ID
     *
     * @param string $customerId
     * @return Customer
     * @throws IFSException
     */
    public function find(string $customerId): Customer
    {
        $response = $this->client->get("{$this->endpoint}/{$customerId}");

        return $this->client->getResponseFactory()
            ->createCustomer($response->getData());
    }

    /**
     * List all customers with optional filters
     *
     * @param array $filters
     * @return array
     * @throws IFSException
     */
    public function list(array $filters = [],$batchSize=15): array
    {
        $filters['$top']=$batchSize;
        $response = $this->client->get($this->endpoint.'?$top='.$batchSize, $filters);
        return $this->client
                    ->getResponseFactory()
                    ->createCustomerCollection($response->getData() ?? []);
    }

    /**
     * Create a new customer
     *
     * @param array $data
     * @return Customer
     * @throws IFSException
     */
    public function create(array $data): Customer
    {
        $response = $this->client->post($this->endpoint, $data);

        return $this->client->getResponseFactory()
            ->createCustomer($response->getData());
    }

    /**
     * Update an existing customer
     *
     * @param string $customerId
     * @param array $data
     * @return Customer
     * @throws IFSException
     */
    public function update(string $customerId, array $data): Customer
    {
        $response = $this->client->put("{$this->endpoint}/{$customerId}", $data);

        return $this->client->getResponseFactory()
            ->createCustomer($response->getData());
    }

    /**
     * Delete a customer
     *
     * @param string $customerId
     * @return bool
     * @throws IFSException
     */
    public function delete(string $customerId): bool
    {
        $response = $this->client->delete("{$this->endpoint}/{$customerId}");

        return $response->isSuccessful();
    }

    /**
     * Search for customers by name, email, or other criteria
     *
     * @param string $query
     * @param array $filters
     * @return array
     * @throws IFSException
     */
    public function search(string $query, array $filters = []): array
    {
        $filters['q'] = $query;

        $response = $this->client->get("{$this->endpoint}/search", $filters);

        return $this->client->getResponseFactory()
            ->createCustomerCollection($response->getData()['data'] ?? []);
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
